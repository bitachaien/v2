<?php

namespace app\model;

use support\Model;

class HemaiJoin extends Model
{
    protected $table = 'caipiao_hemai_join';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    protected $fillable = [
        'hemai_id',
        'uid',
        'username',
        'num',
        'amount',
        'winamount',
        'status',
        'jointime',
        'ip',
    ];
    
    protected $casts = [
        'hemai_id' => 'integer',
        'uid' => 'integer',
        'num' => 'integer',
        'amount' => 'decimal:2',
        'winamount' => 'decimal:2',
        'status' => 'integer',
        'jointime' => 'integer',
    ];
    
    const STATUS_BUYING = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_REFUNDED = 2;
    
    public function hemai()
    {
        return $this->belongsTo(Hemai::class, 'hemai_id');
    }
    
    public function getStatusTextAttribute()
    {
        $map = [
            self::STATUS_BUYING => '认购中',
            self::STATUS_COMPLETED => 'Đã hoàn thành',
            self::STATUS_REFUNDED => '已退款',
        ];
        return $map[$this->status] ?? '未知';
    }
}
