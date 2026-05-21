<?php

declare(strict_types=1);

namespace app\service;

use support\Db;
use support\Log;
use app\common\Result;
use app\constant\BannerConstant;
use app\dto\BannerDTO;
use app\cache\BannerCache;
use app\validator\BannerValidator;
use app\exception\BannerException;


class BannerService
{
    
    private const TABLE_NAME = 'yzz_banner';

    
    public function getApiList(int $platform = BannerConstant::PLATFORM_ALL, int $limit = BannerConstant::DEFAULT_API_LIMIT): array
    {
        try {
            
            $cached = BannerCache::getList($platform, $limit);
            if ($cached !== null) {
                return Result::page($cached, count($cached))->toArray();
            }

            $now = time();

            $query = Db::table(self::TABLE_NAME)
                ->where('status', BannerConstant::STATUS_ENABLED);

            
            if ($platform === BannerConstant::PLATFORM_PC || $platform === BannerConstant::PLATFORM_MOBILE) {
                $query->where(function ($q) use ($platform) {
                    $q->where('platform', BannerConstant::PLATFORM_ALL)
                        ->orWhere('platform', $platform);
                });
            }

            
            $query->where(function ($q) use ($now) {
                $q->where(function ($subQ) use ($now) {
                    $subQ->whereNull('start_time')
                        ->orWhere('start_time', '<=', $now);
                });
                $q->where(function ($subQ) use ($now) {
                    $subQ->whereNull('end_time')
                        ->orWhere('end_time', '>=', $now);
                });
            });

            $banners = $query->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            
            $list = [];
            foreach ($banners as $banner) {
                $dto = BannerDTO::fromModel($banner);
                $list[] = $dto->toApiArray();
            }

            
            BannerCache::setList($platform, $limit, $list);

            return Result::page($list, count($list))->toArray();

        } catch (BannerException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('轮播图Lấy dữ liệu thất bại', [
                'error'    => $e->getMessage(),
                'platform' => $platform,
                'limit'    => $limit,
                'trace'    => $e->getTraceAsString(),
            ]);
            return Result::serverError()->toArray();
        }
    }

    
    public function getApiDetail(int $id): array
    {
        try {
            BannerValidator::validateId($id);

            
            $cached = BannerCache::getDetail($id);
            if ($cached !== null) {
                return Result::success($cached)->toArray();
            }

            $banner = Db::table(self::TABLE_NAME)
                ->where('id', $id)
                ->where('status', BannerConstant::STATUS_ENABLED)
                ->first();

            if (!$banner) {
                throw BannerException::notExists($id);
            }

            $dto = BannerDTO::fromModel($banner);
            $data = $dto->toApiArray();
            $data['remark'] = $dto->remark;

            
            BannerCache::setDetail($id, $data);

            return Result::success($data)->toArray();

        } catch (BannerException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('轮播图Chi tiếtLấy dữ liệu thất bại', [
                'error' => $e->getMessage(),
                'id'    => $id,
            ]);
            return Result::serverError()->toArray();
        }
    }

    
    public function getAdminList(array $params): array
    {
        try {
            $page     = (int)($params['page'] ?? 1);
            $limit    = (int)($params['limit'] ?? BannerConstant::DEFAULT_PAGE_SIZE);
            $platform = $params['platform'] ?? '';
            $status   = $params['status'] ?? '';

            $query = Db::table(self::TABLE_NAME);

            
            if ($platform !== '') {
                $query->where('platform', (int)$platform);
            }

            
            if ($status !== '') {
                $query->where('status', (int)$status);
            }

            $count  = $query->count();
            $offset = ($page - 1) * $limit;

            $list = $query->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            
            $result = [];
            foreach ($list as $item) {
                $dto = BannerDTO::fromModel($item);
                $result[] = $dto->toDetailArray();
            }

            return Result::page($result, $count)->toArray();

        } catch (\Throwable $e) {
            Log::error('后台轮播图列表Lấy dữ liệu thất bại', [
                'error'  => $e->getMessage(),
                'params' => $params,
            ]);
            return Result::serverError()->toArray();
        }
    }

    
    public function create(array $data): array
    {
        try {
            
            BannerValidator::validateCreate($data);

            
            $dto = BannerDTO::fromArray($data);
            $insertData = $dto->toDbArray();

            $id = Db::table(self::TABLE_NAME)->insertGetId($insertData);

            if (!$id) {
                throw BannerException::createFailed();
            }

            
            BannerCache::refresh();

            Log::info('轮播图创建Thành công', ['id' => $id, 'title' => $dto->title]);

            return Result::success(['id' => $id], 'ThêmThành công')->toArray();

        } catch (BannerException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('轮播图创建Thất bại', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);
            return Result::fail('ThêmThất bại: ' . $e->getMessage())->toArray();
        }
    }

    
    public function update(int $id, array $data): array
    {
        try {
            
            BannerValidator::validateUpdate($id, $data);

            
            $exists = Db::table(self::TABLE_NAME)->where('id', $id)->exists();
            if (!$exists) {
                throw BannerException::notExists($id);
            }

            
            $updateData = ['updated_at' => time()];

            $allowFields = ['title', 'image', 'link', 'platform', 'sort', 'status', 'remark'];
            foreach ($allowFields as $field) {
                if (isset($data[$field]) && $data[$field] !== null) {
                    $updateData[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
                }
            }

            
            if (!empty($data['start_time'])) {
                $updateData['start_time'] = is_numeric($data['start_time'])
                    ? (int)$data['start_time']
                    : strtotime($data['start_time']);
            }
            if (!empty($data['end_time'])) {
                $updateData['end_time'] = is_numeric($data['end_time'])
                    ? (int)$data['end_time']
                    : strtotime($data['end_time']);
            }

            $result = Db::table(self::TABLE_NAME)
                ->where('id', $id)
                ->update($updateData);

            if ($result === false) {
                throw BannerException::updateFailed();
            }

            
            BannerCache::refresh($id);

            Log::info('轮播图更新Thành công', ['id' => $id]);

            return Result::success(null, 'SửaThành công')->toArray();

        } catch (BannerException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('轮播图更新Thất bại', [
                'error' => $e->getMessage(),
                'id'    => $id,
                'data'  => $data,
            ]);
            return Result::fail('SửaThất bại')->toArray();
        }
    }

    
    public function delete(int $id): array
    {
        try {
            BannerValidator::validateId($id);

            $result = Db::table(self::TABLE_NAME)->where('id', $id)->delete();

            if (!$result) {
                throw BannerException::deleteFailed();
            }

            
            BannerCache::refresh($id);

            Log::info('轮播图XóaThành công', ['id' => $id]);

            return Result::success(null, 'XóaThành công')->toArray();

        } catch (BannerException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('轮播图XóaThất bại', [
                'error' => $e->getMessage(),
                'id'    => $id,
            ]);
            return Result::fail('XóaThất bại')->toArray();
        }
    }

    
    public function batchDelete(array $ids): array
    {
        try {
            BannerValidator::validateBatchIds($ids);

            $ids = array_map('intval', $ids);
            $result = Db::table(self::TABLE_NAME)->whereIn('id', $ids)->delete();

            if (!$result) {
                throw BannerException::deleteFailed();
            }

            
            BannerCache::refresh();

            Log::info('轮播图批量XóaThành công', ['ids' => $ids, 'count' => count($ids)]);

            return Result::success(null, 'XóaThành công')->toArray();

        } catch (BannerException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('轮播图批量XóaThất bại', [
                'error' => $e->getMessage(),
                'ids'   => $ids,
            ]);
            return Result::fail('XóaThất bại')->toArray();
        }
    }

    
    public function updateStatus(int $id, int $status): array
    {
        try {
            BannerValidator::validateId($id);
            BannerValidator::validateStatus($status);

            $result = Db::table(self::TABLE_NAME)
                ->where('id', $id)
                ->update([
                    'status'     => $status,
                    'updated_at' => time(),
                ]);

            if ($result === false) {
                throw BannerException::updateFailed();
            }

            
            BannerCache::refresh($id);

            $statusText = BannerConstant::getStatusText($status);
            Log::info('轮播图状态更新Thành công', ['id' => $id, 'status' => $statusText]);

            return Result::success(null, '状态更新Thành công')->toArray();

        } catch (BannerException $e) {
            return $e->toResult()->toArray();
        } catch (\Throwable $e) {
            Log::error('轮播图状态更新Thất bại', [
                'error'  => $e->getMessage(),
                'id'     => $id,
                'status' => $status,
            ]);
            return Result::fail('状态更新Thất bại')->toArray();
        }
    }

    
    public function getDetail(int $id): ?array
    {
        try {
            if ($id <= 0) {
                return null;
            }

            $info = Db::table(self::TABLE_NAME)->where('id', $id)->first();

            if (!$info) {
                return null;
            }

            $dto = BannerDTO::fromModel($info);

            return $dto->toDetailArray();

        } catch (\Throwable $e) {
            Log::error('Lấy轮播图Chi tiếtThất bại', [
                'error' => $e->getMessage(),
                'id'    => $id,
            ]);
            return null;
        }
    }

    
    public function exists(int $id): bool
    {
        return Db::table(self::TABLE_NAME)->where('id', $id)->exists();
    }

    
    public function getEnabledCount(): int
    {
        return Db::table(self::TABLE_NAME)
            ->where('status', BannerConstant::STATUS_ENABLED)
            ->count();
    }
}
