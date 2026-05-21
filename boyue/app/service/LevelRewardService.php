<?php

declare(strict_types=1);

namespace app\service;

use support\Db;
use support\Log;
use app\common\Result;
use app\constant\LevelRewardConstant;
use app\dto\LevelRewardDTO;
use app\validator\LevelRewardValidator;
use app\exception\LevelRewardException;


class LevelRewardService
{
    

    
    public function getRewardInfo(int $uid): array
    {
        try {
            LevelRewardValidator::validateUserId($uid);

            
            $user = $this->getUserWithLevel($uid);
            if (!$user) {
                throw LevelRewardException::userNotFound($uid);
            }

            $currentLevelId = (int)($user->groupid ?? 1);
            
            
            $pendingReward = (float)($user->jinjijilu ?? 0);
            
            
            $canClaim = $pendingReward > 0;
            
            
            $totalBetting = Db::table('caipiao_touzhu')
                ->where('uid', $uid)
                ->sum('amount') ?? 0;
            
            
            $progress = $this->calculateProgress($uid, $currentLevelId);

            $data = LevelRewardDTO::levelInfoToApi($user, $pendingReward, $canClaim, $progress);
            $data['totalBetting'] = (float)$totalBetting;

            return Result::success($data)->toArray();

        } catch (LevelRewardException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('LấyThăng cấp奖励信息Thất bại', [
                'error' => $e->getMessage(),
                'uid'   => $uid,
            ]);
            return Result::serverError()->toArray();
        }
    }

    
    public function getRecordList(int $uid, array $params = []): array
    {
        try {
            LevelRewardValidator::validateUserId($uid);
            $pagination = LevelRewardValidator::validatePagination($params);

            
            $query = Db::table(LevelRewardConstant::TABLE_MONEY_LOG)
                ->where('uid', $uid)
                ->where('type', LevelRewardConstant::MONEYLOG_TYPE);

            $total = $query->count();

            $records = $query->orderBy('id', 'desc')
                ->offset($pagination['offset'])
                ->limit($pagination['page_size'])
                ->get();

            
            $list = $records->map(function ($item) {
                return [
                    'id'           => $item->id,
                    'orderNo'      => $item->trano ?? '',
                    'amount'       => (float)$item->amount,
                    'balanceBefore'=> (float)($item->amountbefor ?? 0),
                    'balanceAfter' => (float)($item->amountafter ?? 0),
                    'remark'       => $item->remark ?? 'Thăng cấp奖励',
                    'createTime'   => $item->oddtime ? date('Y-m-d H:i:s', $item->oddtime) : '',
                ];
            })->toArray();

            $data = LevelRewardDTO::paginatedResult(
                $list, 
                $total, 
                $pagination['page'], 
                $pagination['page_size']
            );

            return Result::success($data)->toArray();

        } catch (LevelRewardException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('LấyThăng cấplịch sử thất bại', [
                'error' => $e->getMessage(),
                'uid'   => $uid,
            ]);
            return Result::serverError()->toArray();
        }
    }

    
    public function getLevelConfigs(): array
    {
        try {
            $configs = Db::table(LevelRewardConstant::TABLE_MEMBER_GROUP)
                ->where('groupname', 'like', 'VIP%')
                ->orderBy('groupid', 'asc')
                ->get();
            
            $levelConfigs = Db::table(LevelRewardConstant::TABLE_LEVEL_CONFIG)
                ->where('is_enabled', 1)
                ->get()
                ->keyBy('level_id');
            
            $cumulativeRequired = 0;
            $data = [];
            
            foreach ($configs as $item) {
                $shengjiedu = is_numeric($item->shengjiedu) ? (int)$item->shengjiedu : 0;
                $cumulativeRequired += $shengjiedu;
                
                $levelId = (int)$item->groupid;
                $extraConfig = $levelConfigs[$levelId] ?? null;
                
                $data[] = [
                    'levelId'            => $levelId,
                    'levelName'          => $item->groupname,
                    'requiredPoints'     => $shengjiedu,
                    'cumulativeRequired' => $cumulativeRequired,
                    'rewardAmount'       => (float)($item->jjje ?? 0),
                    'freeWithdrawTimes'  => (int)($item->free_withdraw_times ?? 0),
                    'weeklyBetting'      => (float)($item->weekly_betting ?? 0),
                    'weeklyBonus'        => (float)($item->weekly_bonus ?? 0),
                    'monthlyBetting'     => (float)($item->monthly_betting ?? 0),
                    'monthlyBonus'       => (float)($item->monthly_bonus ?? 0),
                    'withdrawLimit'      => $extraConfig ? (float)$extraConfig->daily_withdraw_limit : 0,
                    'withdrawTimes'      => 0,
                ];
            }
            
            return Result::success($data)->toArray();
        } catch (\Throwable $e) {
            Log::error('LấyCấp độcấu hình thất bại', ['error' => $e->getMessage()]);
            return Result::serverError()->toArray();
        }
    }

    

    
    public function claimReward(int $uid): array
    {
        try {
            LevelRewardValidator::validateUserId($uid);

            
            $user = $this->getUserWithLevel($uid);
            if (!$user) {
                throw LevelRewardException::userNotFound($uid);
            }

            
            $amount = (float)($user->jinjijilu ?? 0);
            
            if ($amount <= 0) {
                throw LevelRewardException::noRewardAvailable();
            }

            
            $result = $this->executeClaimReward($uid, $user, $amount);

            Log::info('领取Thăng cấp奖励Thành công', [
                'uid'      => $uid,
                'amount'   => $amount,
                'order_id' => $result['orderId'],
            ]);

            return Result::success($result, '领取Thành công')->toArray();

        } catch (LevelRewardException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('领取Thăng cấp奖励Thất bại', [
                'error' => $e->getMessage(),
                'uid'   => $uid,
            ]);
            return Result::fail($e->getMessage())->toArray();
        }
    }

    

    
    private function getUserWithLevel(int $uid): ?object
    {
        return Db::table(LevelRewardConstant::TABLE_MEMBER . ' as m')
            ->leftJoin(LevelRewardConstant::TABLE_MEMBER_GROUP . ' as g', 'm.groupid', '=', 'g.groupid')
            ->where('m.id', $uid)
            ->select('m.*', 'g.groupname', 'g.shengjiedu', 'g.jjje')
            ->first();
    }

    
    private function calculateProgress(int $uid, int $currentLevelId): float
    {
        
        $allLevels = Db::table(LevelRewardConstant::TABLE_MEMBER_GROUP)
            ->orderBy('groupid', 'asc')
            ->get();
        
        $cumulativeRequired = 0;
        $nextLevelRequired = 0;
        $foundCurrent = false;
        
        foreach ($allLevels as $level) {
            if (!is_numeric($level->shengjiedu)) {
                continue;
            }
            $cumulativeRequired += (int)$level->shengjiedu;
            
            if ((int)$level->groupid == $currentLevelId) {
                $foundCurrent = true;
                continue;
            }
            if ($foundCurrent && $nextLevelRequired == 0) {
                $nextLevelRequired = $cumulativeRequired;
                break;
            }
        }
        
        if ($nextLevelRequired <= 0) {
            return 100.0; 
        }

        
        $totalPoints = Db::table('caipiao_touzhu')
            ->where('uid', $uid)
            ->sum('amount') ?? 0;

        return min(100, ($totalPoints / $nextLevelRequired) * 100);
    }

    
    private function executeClaimReward(int $uid, object $user, float $amount): array
    {
        Db::beginTransaction();

        try {
            $orderId = LevelRewardConstant::generateOrderId();
            $levelId = (int)($user->groupid ?? 1);
            $levelName = $user->groupname ?? 'VIP' . $levelId;
            $balanceBefore = (float)($user->balance ?? 0);
            $balanceAfter = $balanceBefore + $amount;

            
            Db::table(LevelRewardConstant::TABLE_MEMBER)
                ->where('id', $uid)
                ->update([
                    'balance'   => Db::raw("balance + {$amount}"),
                    'jinjijilu' => 0,
                ]);

            
            Db::table(LevelRewardConstant::TABLE_MONEY_LOG)->insert([
                'trano'       => $orderId,
                'uid'         => $uid,
                'username'    => $user->username ?? '',
                'type'        => LevelRewardConstant::MONEYLOG_TYPE,
                'typename'    => LevelRewardConstant::MONEYLOG_TYPE_NAME,
                'amount'      => $amount,
                'amountbefor' => $balanceBefore,
                'amountafter' => $balanceAfter,
                'oddtime'     => time(),
                'remark'      => "Thăng cấp至{$levelName}奖励",
            ]);

            Db::commit();

            return LevelRewardDTO::claimResultToApi($orderId, $amount, $levelName);

        } catch (\Throwable $e) {
            Db::rollBack();
            throw LevelRewardException::claimFailed($e->getMessage());
        }
    }

    
    private function getLevelNameById(int $levelId): string
    {
        $config = Db::table(LevelRewardConstant::TABLE_MEMBER_GROUP)
            ->where('groupid', $levelId)
            ->first();
        
        return $config ? $config->groupname : 'VIP' . $levelId;
    }
}
