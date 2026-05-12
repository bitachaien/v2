<?php

declare(strict_types=1);

namespace plugin\admin\app\model;

use app\constant\BannerConstant;

/**
 * 轮播图模型
 *
 * 用于轮播图数据的ORM操作
 *
 * @package plugin\admin\app\model
 * @author  System
 * @version 1.0.0
 *
 * @property int         $id          主键ID
 * @property string      $title       标题
 * @property string      $image       图片地址
 * @property string|null $link        跳转链接
 * @property int         $platform    平台类型
 * @property int         $sort        排序值
 * @property int         $status      状态
 * @property int|null    $start_time  开始时间
 * @property int|null    $end_time    结束时间
 * @property string|null $remark      备注
 * @property int         $created_at  创建时间
 * @property int         $updated_at  更新时间
 */
class Banner extends Base
{
    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'yzz_banner';

    /**
     * 主键字段
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'image',
        'link',
        'platform',
        'sort',
        'status',
        'start_time',
        'end_time',
        'remark',
        'created_at',
        'updated_at',
    ];

    /**
     * 属性类型转换映射
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id'          => 'integer',
        'platform'    => 'integer',
        'sort'        => 'integer',
        'status'      => 'integer',
        'start_time'  => 'integer',
        'end_time'    => 'integer',
        'created_at'  => 'integer',
        'updated_at'  => 'integer',
    ];

    /**
     * 获取平台类型文本
     *
     * @return string
     */
    public function getPlatformTextAttribute(): string
    {
        return BannerConstant::getPlatformText((int)$this->platform);
    }

    /**
     * 获取状态文本
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        return BannerConstant::getStatusText((int)$this->status);
    }

    /**
     * 获取有效期文本
     *
     * @return string
     */
    public function getValidTimeAttribute(): string
    {
        if (!empty($this->start_time) && !empty($this->end_time)) {
            return date('Y-m-d H:i', $this->start_time) . ' ~ ' . date('Y-m-d H:i', $this->end_time);
        }
        return '永久有效';
    }

    /**
     * 判断轮播图是否在有效期内
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $now = time();

        // 检查状态
        if ($this->status !== BannerConstant::STATUS_ENABLED) {
            return false;
        }

        // 检查开始时间
        if (!empty($this->start_time) && $this->start_time > $now) {
            return false;
        }

        // 检查结束时间
        if (!empty($this->end_time) && $this->end_time < $now) {
            return false;
        }

        return true;
    }

    /**
     * 查询启用状态的轮播图
     *
     * @param \Illuminate\Database\Eloquent\Builder $query 查询构造器
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', BannerConstant::STATUS_ENABLED);
    }

    /**
     * 按平台类型查询
     *
     * @param \Illuminate\Database\Eloquent\Builder $query    查询构造器
     * @param int                                    $platform 平台类型
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePlatform($query, int $platform)
    {
        if ($platform === BannerConstant::PLATFORM_PC || $platform === BannerConstant::PLATFORM_MOBILE) {
            return $query->where(function ($q) use ($platform) {
                $q->where('platform', BannerConstant::PLATFORM_ALL)
                    ->orWhere('platform', $platform);
            });
        }
        return $query;
    }
}









