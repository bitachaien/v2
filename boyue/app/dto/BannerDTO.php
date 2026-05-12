<?php

declare(strict_types=1);

namespace app\dto;

use app\constant\BannerConstant;


class BannerDTO
{
    
    public ?int $id = null;

    
    public string $title = '';

    
    public string $image = '';

    
    public string $link = '';

    
    public int $platform = BannerConstant::PLATFORM_ALL;

    
    public int $sort = 0;

    
    public int $status = BannerConstant::STATUS_ENABLED;

    
    public ?int $startTime = null;

    
    public ?int $endTime = null;

    
    public string $remark = '';

    
    public ?int $createdAt = null;

    
    public ?int $updatedAt = null;

    
    public static function fromArray(array $data): self
    {
        $dto = new self();

        if (isset($data['id'])) {
            $dto->id = (int)$data['id'];
        }

        if (isset($data['title'])) {
            $dto->title = trim((string)$data['title']);
        }

        if (isset($data['image'])) {
            $dto->image = trim((string)$data['image']);
        }

        if (isset($data['link'])) {
            $dto->link = trim((string)$data['link']);
        }

        if (isset($data['platform'])) {
            $dto->platform = (int)$data['platform'];
        }

        if (isset($data['sort'])) {
            $dto->sort = (int)$data['sort'];
        }

        if (isset($data['status'])) {
            $dto->status = (int)$data['status'];
        }

        
        if (!empty($data['start_time'])) {
            $dto->startTime = is_numeric($data['start_time'])
                ? (int)$data['start_time']
                : strtotime($data['start_time']);
        }

        if (!empty($data['end_time'])) {
            $dto->endTime = is_numeric($data['end_time'])
                ? (int)$data['end_time']
                : strtotime($data['end_time']);
        }

        if (isset($data['remark'])) {
            $dto->remark = trim((string)$data['remark']);
        }

        if (isset($data['created_at'])) {
            $dto->createdAt = (int)$data['created_at'];
        }

        if (isset($data['updated_at'])) {
            $dto->updatedAt = (int)$data['updated_at'];
        }

        return $dto;
    }

    
    public static function fromModel(object $model): self
    {
        $dto = new self();

        $dto->id         = (int)$model->id;
        $dto->title      = (string)($model->title ?? '');
        $dto->image      = (string)($model->image ?? '');
        $dto->link       = (string)($model->link ?? '');
        $dto->platform   = (int)($model->platform ?? 0);
        $dto->sort       = (int)($model->sort ?? 0);
        $dto->status     = (int)($model->status ?? 0);
        $dto->startTime  = $model->start_time ? (int)$model->start_time : null;
        $dto->endTime    = $model->end_time ? (int)$model->end_time : null;
        $dto->remark     = (string)($model->remark ?? '');
        $dto->createdAt  = isset($model->created_at) ? (int)$model->created_at : null;
        $dto->updatedAt  = isset($model->updated_at) ? (int)$model->updated_at : null;

        return $dto;
    }

    
    public function toDbArray(): array
    {
        $data = [
            'title'       => $this->title,
            'image'       => $this->image,
            'link'        => $this->link,
            'platform'    => $this->platform,
            'sort'        => $this->sort,
            'status'      => $this->status,
            'start_time'  => $this->startTime,
            'end_time'    => $this->endTime,
            'remark'      => $this->remark,
            'updated_at'  => time(),
        ];

        
        if ($this->id === null) {
            $data['created_at'] = time();
        }

        return $data;
    }

    
    public function toApiArray(): array
    {
        return [
            'id'       => $this->id,
            'title'    => $this->title,
            'image'    => $this->image,
            'link'     => $this->link,
            'platform' => $this->platform,
            'sort'     => $this->sort,
        ];
    }

    
    public function toDetailArray(): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'image'         => $this->image,
            'link'          => $this->link,
            'platform'      => $this->platform,
            'platform_text' => BannerConstant::getPlatformText($this->platform),
            'sort'          => $this->sort,
            'status'        => $this->status,
            'status_text'   => BannerConstant::getStatusText($this->status),
            'start_time'    => $this->startTime,
            'end_time'      => $this->endTime,
            'valid_time'    => $this->getValidTimeText(),
            'remark'           => $this->remark,
            'created_at'       => $this->createdAt,
            'created_at_text'  => $this->createdAt ? date('Y-m-d H:i:s', $this->createdAt) : '',
            'updated_at'       => $this->updatedAt,
            'updated_at_text'  => $this->updatedAt ? date('Y-m-d H:i:s', $this->updatedAt) : '',
        ];
    }

    
    public function getValidTimeText(): string
    {
        if ($this->startTime && $this->endTime) {
            return date('Y-m-d H:i', $this->startTime) . ' ~ ' . date('Y-m-d H:i', $this->endTime);
        }
        return '永久有效';
    }

    
    public function isValid(): bool
    {
        if ($this->status !== BannerConstant::STATUS_ENABLED) {
            return false;
        }

        $now = time();

        if ($this->startTime && $this->startTime > $now) {
            return false;
        }

        if ($this->endTime && $this->endTime < $now) {
            return false;
        }

        return true;
    }
}
