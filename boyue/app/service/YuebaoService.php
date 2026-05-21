<?php

declare(strict_types=1);

namespace app\service;

use support\Db;
use support\Log;
use app\common\Result;
use app\constant\YuebaoConstant;
use app\dto\YuebaoDTO;
use app\cache\YuebaoCache;
use app\validator\YuebaoValidator;
use app\exception\YuebaoException;

class YuebaoService
{
    
    private const TABLE_STATS = 'yzz_yuebao_stats';

    
    private const TABLE_PRODUCT = 'yzz_yuebao_product';

    
    private const TABLE_HOLDING = 'yzz_yuebao_holding';

    
    private const TABLE_RECORD = 'yzz_yuebao_record';

    
    private const TABLE_MEMBER = 'caipiao_member';

    
    private const TABLE_MONEYLOG = 'caipiao_moneylog';

    

    
    public function getInfo(int $uid): array
    {
        try {
            YuebaoValidator::validateUserId($uid);

            
            $stats = $this->getOrCreateStats($uid);
            $data = YuebaoDTO::statsFromModel($stats);

            
            $holdingAmount = Db::table(self::TABLE_HOLDING)
                ->where('uid', $uid)
                ->where('status', 'running')
                ->sum('amount');
            $data['current_amount'] = number_format((float)($holdingAmount ?? 0), 2, '.', '');

            
            $pendingInterest = Db::table(self::TABLE_HOLDING)
                ->where('uid', $uid)
                ->where('status', 'running')
                ->sum('actual_interest');
            $data['pending_interest'] = number_format((float)($pendingInterest ?? 0), 2, '.', '');

            return Result::success($data)->toArray();

        } catch (YuebaoException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('Lấy số dư宝信息Thất bại', [
                'error' => $e->getMessage(),
                'uid'   => $uid,
            ]);
            return Result::serverError()->toArray();
        }
    }

    
    public function getProducts(): array
    {
        try {
            
            $cached = YuebaoCache::getProducts();
            if ($cached !== null) {
                return Result::success($cached)->toArray();
            }

            $products = Db::table(self::TABLE_PRODUCT)
                ->where('status', YuebaoConstant::PRODUCT_STATUS_ENABLED)
                ->orderBy('sort', 'asc')
                ->get();

            $list = [];
            foreach ($products as $product) {
                $list[] = YuebaoDTO::productToApi($product);
            }

            
            YuebaoCache::setProducts($list);

            return Result::success($list)->toArray();

        } catch (\Throwable $e) {
            Log::error('Lấy产品列表Thất bại', ['error' => $e->getMessage()]);
            return Result::serverError()->toArray();
        }
    }
    
    

    
    public function transferIn(int $uid, array $data): array
    {
        try {
            
            YuebaoValidator::validateUserId($uid);

            
            if (!YuebaoCache::checkTransferInLimit($uid)) {
                throw YuebaoException::transferInRateLimited();
            }

            
            YuebaoValidator::validateTransferIn($data);

            $productId = (int)($data['product_id'] ?? $data['productId'] ?? 0);
            $amount = (float)$data['amount'];
            $password = (string)($data['password'] ?? '');

            
            $product = Db::table(self::TABLE_PRODUCT)
                ->where('id', $productId)
                ->where('status', YuebaoConstant::PRODUCT_STATUS_ENABLED)
                ->first();

            if (!$product) {
                throw YuebaoException::productNotFound($productId);
            }

            
            $minAmount = (float)($product->min_amount ?? 0);
            YuebaoValidator::validateMinAmount($amount, $minAmount);

            
            $user = Db::table(self::TABLE_MEMBER)->where('id', $uid)->first();
            if (!$user) {
                throw YuebaoException::userNotFound($uid);
            }

            
            YuebaoValidator::validateFundPassword($password, $user->paypassword ?? '');

            
            $userBalance = (float)($user->balance ?? 0);
            YuebaoValidator::validateBalance($amount, $userBalance);

            
            $result = $this->executeTransferIn($uid, $user, $product, $amount);

            
            YuebaoCache::refresh($uid);

            Log::info('Số dư宝转入Thành công', [
                'uid'        => $uid,
                'order_id'   => $result['order_id'],
                'product_id' => $productId,
                'amount'     => $amount,
            ]);

            return Result::success($result, '转入Thành công')->toArray();

        } catch (YuebaoException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('Số dư宝转入Thất bại', [
                'error' => $e->getMessage(),
                'uid'   => $uid,
                'data'  => $data,
            ]);
            return Result::fail($e->getMessage())->toArray();
        }
    }

    
    private function executeTransferIn(int $uid, object $user, object $product, float $amount): array
    {
        Db::beginTransaction();

        try {
            $orderId = YuebaoConstant::generateOrderId(YuebaoConstant::ORDER_PREFIX_IN);
            $now = time();
            
            
            $safeAmount = (float)abs($amount);

            
            $lockedUser = Db::table(self::TABLE_MEMBER)->where('id', $uid)->lockForUpdate()->first();
            if ((float)$lockedUser->balance < $safeAmount) {
                throw YuebaoException::insufficientBalance();
            }
            Db::table(self::TABLE_MEMBER)->where('id', $uid)->decrement('balance', $safeAmount);

            
            $userAfter = Db::table(self::TABLE_MEMBER)->where('id', $uid)->first();

            
            $stats = $this->getOrCreateStats($uid);
            $isCurrent = YuebaoConstant::isCurrentProduct($product->type ?? 'current');

            if ($isCurrent) {
                
                Db::table(self::TABLE_STATS)->where('uid', $uid)->increment('current_amount', $amount);

                
                Db::table(self::TABLE_HOLDING)->insert([
                    'order_id'          => $orderId,
                    'uid'               => $uid,
                    'username'          => $user->username,
                    'product_id'        => $product->id,
                    'product_name'      => $product->name,
                    'amount'            => $amount,
                    'rate'              => $product->rate,
                    'duration_days'     => 0,
                    'expected_interest' => 0,
                    'actual_interest'   => 0,
                    'status'            => YuebaoConstant::HOLDING_STATUS_RUNNING,
                    'create_time'       => $now,
                    'end_time'          => 0,
                ]);

                
                Db::table(self::TABLE_MEMBER)->where('id', $uid)->update([
                    'yebmoney' => Db::raw("yebmoney + {$safeAmount}"),
                    'yebtime'  => $now
                ]);

                $afterYebao = (float)$stats->current_amount + $amount;
            } else {
                
                Db::table(self::TABLE_STATS)->where('uid', $uid)->increment('fixed_amount', $amount);

                $endtime = $now + ((int)$product->duration_days * 86400);
                
                $expectedInterest = $amount * (float)$product->rate;

                Db::table(self::TABLE_HOLDING)->insert([
                    'order_id'          => $orderId,
                    'uid'               => $uid,
                    'username'          => $user->username,
                    'product_id'        => $product->id,
                    'product_name'      => $product->name,
                    'amount'            => $amount,
                    'rate'              => $product->rate,
                    'duration_days'     => $product->duration_days,
                    'expected_interest' => $expectedInterest,
                    'actual_interest'   => 0,
                    'status'            => YuebaoConstant::HOLDING_STATUS_RUNNING,
                    'create_time'       => $now,
                    'end_time'          => $endtime,
                ]);

                
                Db::table(self::TABLE_MEMBER)->where('id', $uid)->increment('dyebmoney', $safeAmount);

                $afterYebao = (float)$stats->fixed_amount + $amount;
            }

            
            Db::table(self::TABLE_STATS)->where('uid', $uid)->update([
                'is_open'     => 1,
                'update_time' => $now,
            ]);

            
            Db::table(self::TABLE_RECORD)->insert([
                'order_id'       => $orderId,
                'uid'            => $uid,
                'username'       => $user->username,
                'type'           => YuebaoConstant::RECORD_TYPE_DEPOSIT,
                'product_id'     => $product->id,
                'product_name'   => $product->name,
                'amount'         => $amount,
                'before_balance' => $user->balance,
                'after_balance'  => $userAfter->balance,
                'before_yebao'   => $afterYebao - $amount,
                'after_yebao'    => $afterYebao,
                'status'         => YuebaoConstant::RECORD_STATUS_SUCCESS,
                'remark'         => "转入Số dư宝-{$product->name}",
                'create_time'    => $now,
                'ip'             => $this->getClientIp(),
            ]);

            
            Db::table(self::TABLE_MONEYLOG)->insert([
                'uid'      => $uid,
                'username' => $user->username,
                'type'     => YuebaoConstant::MONEYLOG_TYPE_TRANSFER_IN,
                'typename' => 'Số dư宝转入',
                'trano'    => $orderId,
                'amount'   => -$amount,
                'before'   => $user->balance,
                'after'    => $userAfter->balance,
                'remark'   => "转入Số dư宝-{$product->name}",
                'addtime'  => $now,
                'ip'       => $this->getClientIp(),
            ]);

            Db::commit();

            return YuebaoDTO::transferInResult($orderId, (float)$userAfter->balance);

        } catch (\Throwable $e) {
            Db::rollBack();
            throw YuebaoException::transferInFailed($e->getMessage());
        }
    }
    
    
    public function transferOut(int $uid, array $data): array
    {
        try {
            YuebaoValidator::validateUserId($uid);
            YuebaoValidator::validateTransferOut($data);

            
            if (!YuebaoCache::checkTransferOutLimit($uid)) {
                throw YuebaoException::transferOutRateLimited();
            }

            $amount = (float)$data['amount'];
            $password = (string)($data['password'] ?? '');

            
            $user = Db::table(self::TABLE_MEMBER)->where('id', $uid)->first();
            if (!$user) {
                throw YuebaoException::userNotFound($uid);
            }

            
            YuebaoValidator::validateFundPassword($password, $user->paypassword ?? '');

            
            $stats = $this->getOrCreateStats($uid);
            $currentAmount = (float)($stats->current_amount ?? 0);

            
            YuebaoValidator::validateCurrentBalance($amount, $currentAmount);

            
            $result = $this->executeTransferOut($uid, $user, $stats, $amount);

            
            YuebaoCache::refresh($uid);

            Log::info('Số dư宝转出Thành công', [
                'uid'      => $uid,
                'order_id' => $result['order_id'],
                'amount'   => $amount,
            ]);

            return Result::success($result, '转出Thành công')->toArray();

        } catch (YuebaoException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('Số dư宝转出Thất bại', [
                'error' => $e->getMessage(),
                'uid'   => $uid,
                'data'  => $data,
            ]);
            return Result::fail($e->getMessage())->toArray();
        }
    }

    
    private function executeTransferOut(int $uid, object $user, object $stats, float $amount): array
    {
        Db::beginTransaction();

        try {
            $orderId = YuebaoConstant::generateOrderId(YuebaoConstant::ORDER_PREFIX_OUT);
            $now = time();
            
            
            $safeAmount = (float)abs($amount);

            
            $lockedStats = Db::table(self::TABLE_STATS)->where('uid', $uid)->lockForUpdate()->first();
            if ((float)$lockedStats->current_amount < $safeAmount) {
                throw YuebaoException::insufficientYuebaoBalance();
            }
            Db::table(self::TABLE_STATS)->where('uid', $uid)->decrement('current_amount', $safeAmount);

            
            Db::table(self::TABLE_MEMBER)->where('id', $uid)->update([
                'balance'  => Db::raw("balance + {$safeAmount}"),
                'yebmoney' => Db::raw("yebmoney - {$safeAmount}")
            ]);

            
            $this->updateHoldingsForWithdraw($uid, $amount);

            
            $userAfter = Db::table(self::TABLE_MEMBER)->where('id', $uid)->first();

            
            Db::table(self::TABLE_RECORD)->insert([
                'order_id'       => $orderId,
                'uid'            => $uid,
                'username'       => $user->username,
                'type'           => YuebaoConstant::RECORD_TYPE_WITHDRAW,
                'amount'         => $amount,
                'before_balance' => $user->balance,
                'after_balance'  => $userAfter->balance,
                'before_yebao'   => $stats->current_amount,
                'after_yebao'    => (float)$stats->current_amount - $amount,
                'status'         => YuebaoConstant::RECORD_STATUS_SUCCESS,
                'remark'         => 'Số dư宝转出到主钱包',
                'create_time'    => $now,
                'ip'             => $this->getClientIp(),
            ]);

            
            Db::table(self::TABLE_MONEYLOG)->insert([
                'uid'      => $uid,
                'username' => $user->username,
                'type'     => YuebaoConstant::MONEYLOG_TYPE_TRANSFER_OUT,
                'typename' => 'Số dư宝转出',
                'trano'    => $orderId,
                'amount'   => $amount,
                'before'   => $user->balance,
                'after'    => $userAfter->balance,
                'remark'   => 'Số dư宝转出到主钱包',
                'addtime'  => $now,
                'ip'       => $this->getClientIp(),
            ]);

            
            Db::table(self::TABLE_STATS)->where('uid', $uid)->update(['update_time' => $now]);

            Db::commit();

            return YuebaoDTO::transferOutResult($orderId, (float)$userAfter->balance);

        } catch (\Throwable $e) {
            Db::rollBack();
            throw YuebaoException::transferOutFailed($e->getMessage());
        }
    }

    
    private function updateHoldingsForWithdraw(int $uid, float $amount): void
    {
        $holdings = Db::table(self::TABLE_HOLDING)
            ->where('uid', $uid)
            ->where('status', YuebaoConstant::HOLDING_STATUS_RUNNING)
            ->where('duration_days', 0)
            ->orderBy('create_time', 'asc')
            ->get();

        $remainingAmount = $amount;
        foreach ($holdings as $holding) {
            if ($remainingAmount <= 0) break;

            if ((float)$holding->amount <= $remainingAmount) {
                Db::table(self::TABLE_HOLDING)->where('id', $holding->id)->update([
                    'status'      => YuebaoConstant::HOLDING_STATUS_SETTLED,
                    'settle_time' => time(),
                ]);
                $remainingAmount = (float)bcsub((string)$remainingAmount, (string)$holding->amount, 2);
            } else {
                Db::table(self::TABLE_HOLDING)->where('id', $holding->id)->decrement('amount', $remainingAmount);
                $remainingAmount = 0;
            }
        }
    }
    
    

    
    public function getRecords(int $uid, array $params): array
    {
        try {
            YuebaoValidator::validateUserId($uid);
            $params = YuebaoValidator::validateRecordParams($params);

            $query = Db::table(self::TABLE_RECORD)->where('uid', $uid);

            
            $this->applyRecordTypeFilter($query, $params['type']);

            
            $this->applyProductTypeFilter($query, $params['product_type']);

            
            $this->applyDateRangeFilter($query, $params['date_range']);

            
            $total = (clone $query)->count();

            
            $records = $query->orderBy('create_time', 'desc')
                ->offset($params['offset'])
                ->limit($params['page_size'])
                ->get();

            $list = [];
            foreach ($records as $record) {
                $list[] = YuebaoDTO::recordFromModel($record);
            }

            return Result::success([
                'total'        => $total,
                'current_page' => $params['page'],
                'list'         => $list,
            ])->toArray();

        } catch (YuebaoException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('Lấy交易lịch sử thất bại', [
                'error'  => $e->getMessage(),
                'uid'    => $uid,
                'params' => $params,
            ]);
            return Result::serverError()->toArray();
        }
    }

    
    private function applyRecordTypeFilter($query, string $type): void
    {
        if ($type === 'transfer') {
            $query->whereRaw("type IN ('deposit', 'withdraw', 'transfer_in', 'transfer_out')");
        } elseif ($type !== 'all') {
            $query->where('type', $type);
        }
    }

    
    private function applyProductTypeFilter($query, string $productType): void
    {
        if ($productType === 'current') {
            $query->whereRaw("(product_name IS NULL OR product_name = '' OR product_name LIKE '%活期%')");
        } elseif ($productType === 'fixed') {
            $query->where('product_name', 'like', '%定期%');
        }
    }

    
    private function applyDateRangeFilter($query, string $dateRange): void
    {
        if (empty($dateRange)) return;

        $now = time();
        switch ($dateRange) {
            case 'today':
                $query->where('create_time', '>=', strtotime(date('Y-m-d 00:00:00')));
                break;
            case 'yesterday':
                $start = strtotime(date('Y-m-d 00:00:00', $now - 86400));
                $end = strtotime(date('Y-m-d 23:59:59', $now - 86400));
                $query->whereBetween('create_time', [$start, $end]);
                break;
            case 'week':
                $query->where('create_time', '>=', strtotime(date('Y-m-d 00:00:00', $now - 7 * 86400)));
                break;
            case 'month':
                $query->where('create_time', '>=', strtotime(date('Y-m-d 00:00:00', $now - 30 * 86400)));
                break;
        }
    }
    
    
    public function getAnalysis(int $uid, $days = 7): array
    {
        try {
            YuebaoValidator::validateUserId($uid);
            $days = YuebaoValidator::validateAnalysisDays($days);

            
            $startTime = strtotime(date('Y-m-d 00:00:00', time() - ($days - 1) * 86400));

            $incomeRecords = Db::table(self::TABLE_RECORD)
                ->where('uid', $uid)
                ->where('type', YuebaoConstant::RECORD_TYPE_INCOME)
                ->where('create_time', '>=', $startTime)
                ->orderBy('create_time', 'asc')
                ->get();

            
            $incomeByDate = [];
            foreach ($incomeRecords as $record) {
                $date = date('m-d', (int)$record->create_time);
                if (!isset($incomeByDate[$date])) {
                    $incomeByDate[$date] = 0;
                }
                $incomeByDate[$date] += (float)$record->amount;
            }

            
            $stats = $this->getOrCreateStats($uid);

            return Result::success([
                'income_trend'      => YuebaoDTO::incomeTrend($incomeByDate, $days),
                'asset_composition' => YuebaoDTO::assetComposition($stats),
            ])->toArray();

        } catch (YuebaoException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('Lấy资产分析Thất bại', [
                'error' => $e->getMessage(),
                'uid'   => $uid,
                'days'  => $days,
            ]);
            return Result::serverError()->toArray();
        }
    }

    
    public function getHoldings(int $uid, array $params): array
    {
        try {
            YuebaoValidator::validateUserId($uid);
            $params = YuebaoValidator::validateHoldingParams($params);

            $query = Db::table(self::TABLE_HOLDING)->where('uid', $uid);

            if ($params['status'] === 'active') {
                $query->where('status', YuebaoConstant::HOLDING_STATUS_RUNNING);
            } elseif ($params['status'] === 'completed') {
                $query->whereIn('status', [
                    YuebaoConstant::HOLDING_STATUS_DONE,
                    YuebaoConstant::HOLDING_STATUS_SETTLED,
                    YuebaoConstant::HOLDING_STATUS_CANCELED,
                ]);
            }

            $holdings = $query->orderBy('create_time', 'desc')->get();

            $list = [];
            foreach ($holdings as $holding) {
                $list[] = YuebaoDTO::holdingFromModel($holding);
            }

            return Result::success($list)->toArray();

        } catch (YuebaoException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('Lấy持仓列表Thất bại', [
                'error'  => $e->getMessage(),
                'uid'    => $uid,
                'params' => $params,
            ]);
            return Result::serverError()->toArray();
        }
    }

    
    public function exportBill(int $uid, array $data): array
    {
        try {
            YuebaoValidator::validateUserId($uid);
            $params = YuebaoValidator::validateExportParams($data);

            
            

            Log::info('账单导出请求', [
                'uid'        => $uid,
                'email'      => $params['email'],
                'date_range' => $params['date_range'],
            ]);

            return Result::success(null, '账单已发送至您的邮箱，请查收')->toArray();

        } catch (YuebaoException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('导出账单Thất bại', [
                'error' => $e->getMessage(),
                'uid'   => $uid,
                'data'  => $data,
            ]);
            return Result::fail('导出Thất bại，请稍后重试')->toArray();
        }
    }

    

    
    private const CONFIG_NAME = 'yuebao_interest_config';

    
    private const DEFAULT_CONFIG = [
        'settle_cycle' => '1小时',           
        'settle_cycle_hours' => 1,          
        'annual_rate' => '4%',              
        'annual_rate_value' => 0.04,        
        'min_amount' => 20,                 
        'max_interest' => '不限制',          
        'claim_time' => '隔天领取',          
        'audit_multiple' => 1,              
        'rules' => []                       
    ];

    
    public function getConfig(): array
    {
        try {
            $config = Db::table('caipiao_option')
                ->where('name', self::CONFIG_NAME)
                ->value('value');

            if ($config) {
                $data = json_decode($config, true);
                if (is_array($data)) {
                    return Result::success(array_merge(self::DEFAULT_CONFIG, $data))->toArray();
                }
            }

            return Result::success(self::DEFAULT_CONFIG)->toArray();

        } catch (\Throwable $e) {
            Log::error('Lấy利息宝cấu hình thất bại', ['error' => $e->getMessage()]);
            return Result::success(self::DEFAULT_CONFIG)->toArray();
        }
    }

    
    public function saveConfig(array $data): array
    {
        try {
            $config = array_merge(self::DEFAULT_CONFIG, $data);
            $json = json_encode($config, JSON_UNESCAPED_UNICODE);

            $exists = Db::table('caipiao_option')
                ->where('name', self::CONFIG_NAME)
                ->exists();

            if ($exists) {
                Db::table('caipiao_option')
                    ->where('name', self::CONFIG_NAME)
                    ->update(['value' => $json]);
            } else {
                Db::table('caipiao_option')->insert([
                    'name'  => self::CONFIG_NAME,
                    'value' => $json
                ]);
            }

            Log::info('利息宝配置已更新', ['config' => $config]);

            return Result::success(null, 'LưuThành công')->toArray();

        } catch (\Throwable $e) {
            Log::error('Lưu利息宝cấu hình thất bại', ['error' => $e->getMessage()]);
            return Result::fail('LưuThất bại')->toArray();
        }
    }

    
    public function claimInterest(int $uid): array
    {
        try {
            
            $holdings = Db::table(self::TABLE_HOLDING)
                ->where('uid', $uid)
                ->where('status', 'running')
                ->where('actual_interest', '>', 0)
                ->get();

            if ($holdings->isEmpty()) {
                return Result::fail('暂无可领取的收益')->toArray();
            }

            $totalInterest = '0';
            foreach ($holdings as $h) {
                $totalInterest = bcadd($totalInterest, (string)$h->actual_interest, 2);
            }

            if ($totalInterest <= 0) {
                return Result::fail('暂无可领取的收益')->toArray();
            }

            Db::beginTransaction();

            $now = time();
            $member = Db::table(self::TABLE_MEMBER)->where('id', $uid)->first();
            if (!$member) {
                Db::rollBack();
                return Result::fail('Người dùng không tồn tại')->toArray();
            }

            $beforeBalance = (string)$member->balance;
            $afterBalance = bcadd($beforeBalance, $totalInterest, 2);

            
            Db::table(self::TABLE_MEMBER)
                ->where('id', $uid)
                ->update(['balance' => $afterBalance]);

            
            Db::table(self::TABLE_HOLDING)
                ->where('uid', $uid)
                ->where('status', 'running')
                ->where('actual_interest', '>', 0)
                ->update([
                    'actual_interest' => 0,
                    'update_time' => $now
                ]);

            
            $orderId = YuebaoConstant::generateOrderId('YBCLM');
            Db::table(self::TABLE_RECORD)->insert([
                'order_id'       => $orderId,
                'uid'            => $uid,
                'username'       => $member->username,
                'type'           => 'claim',
                'product_id'     => 0,
                'product_name'   => '收益领取',
                'amount'         => $totalInterest,
                'before_balance' => $beforeBalance,
                'after_balance'  => $afterBalance,
                'before_yebao'   => 0,
                'after_yebao'    => 0,
                'status'         => 'success',
                'remark'         => '领取利息宝收益',
                'create_time'    => $now,
            ]);

            
            Db::table('caipiao_fuddetail')->insert([
                'trano'        => $orderId,
                'uid'          => $uid,
                'username'     => $member->username,
                'type'         => 'yuebao_claim',
                'typename'     => '利息宝收益领取',
                'amount'       => $totalInterest,
                'amountbefor'  => $beforeBalance,
                'amountafter'  => $afterBalance,
                'remark'       => '利息宝收益领取',
                'oddtime'      => $now,
            ]);

            
            $stats = Db::table(self::TABLE_STATS)->where('uid', $uid)->first();
            if ($stats) {
                $newTotalInterest = bcadd((string)$stats->total_interest, $totalInterest, 2);
                Db::table(self::TABLE_STATS)
                    ->where('uid', $uid)
                    ->update([
                        'total_interest' => $newTotalInterest,
                        'update_time' => $now
                    ]);
            }

            Db::commit();

            Log::info('Người dùng领取利息宝收益', [
                'uid' => $uid,
                'amount' => $totalInterest
            ]);

            return Result::success([
                'amount' => $totalInterest,
                'balance' => $afterBalance
            ], '领取Thành công')->toArray();

        } catch (\Throwable $e) {
            Db::rollBack();
            Log::error('领取收益Thất bại', [
                'uid' => $uid,
                'error' => $e->getMessage()
            ]);
            return Result::fail('领取Thất bại：' . $e->getMessage())->toArray();
        }
    }

    

    
    private function getOrCreateStats(int $uid): object
    {
        $stats = Db::table(self::TABLE_STATS)->where('uid', $uid)->first();

        if (!$stats) {
            $now = time();
            Db::table(self::TABLE_STATS)->insert([
                'uid'                => $uid,
                'total_interest'     => 0,
                'current_amount'     => 0,
                'fixed_amount'       => 0,
                'yesterday_interest' => 0,
                'seven_day_rate'     => 0,
                'is_open'            => 0,
                'create_time'        => $now,
                'update_time'        => $now,
            ]);

            $stats = Db::table(self::TABLE_STATS)->where('uid', $uid)->first();
        }

        return $stats;
    }

    
    private function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
}
