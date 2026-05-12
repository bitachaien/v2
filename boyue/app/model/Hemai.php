<?php

namespace app\model;

use support\Model;

class Hemai extends Model
{
    protected $table = 'caipiao_hemai';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    protected $fillable = [
        'uid',
        'username',
        'cpname',
        'cptitle',
        'typeid',
        'expect',
        'playid',
        'playtitle',
        'tzcode',
        'amount',
        'hemaipic',
        'buytotal',
        'buyhave',
        'buyed',
        'baodi',
        'baodi_amount',
        'content',
        'status',
        'isdraw',
        'opencode',
        'winamount',
        'endtime',
        'createtime',
        'drawtime',
        'ip',
    ];
    
    protected $casts = [
        'uid' => 'integer',
        'amount' => 'decimal:2',
        'hemaipic' => 'decimal:2',
        'buytotal' => 'integer',
        'buyhave' => 'integer',
        'buyed' => 'integer',
        'baodi' => 'integer',
        'baodi_amount' => 'decimal:2',
        'status' => 'integer',
        'isdraw' => 'integer',
        'winamount' => 'decimal:2',
        'endtime' => 'integer',
        'createtime' => 'integer',
        'drawtime' => 'integer',
    ];
    
    const STATUS_ONGOING = 0;
    const STATUS_CLOSED = 1;
    const STATUS_DRAWN = 2;
    const STATUS_CANCELED = 3;
    
    const DRAW_NOT = 0;
    const DRAW_WIN = 1;
    const DRAW_LOSE = -1;
    
    public function joins()
    {
        return $this->hasMany(HemaiJoin::class, 'hemai_id');
    }
    
    public function getStatusTextAttribute()
    {
        $map = [
            self::STATUS_ONGOING => '进行中',
            self::STATUS_CLOSED => '已截止',
            self::STATUS_DRAWN => '已开奖',
            self::STATUS_CANCELED => '已取消',
        ];
        return $map[$this->status] ?? '未知';
    }
    
    public function getProgressAttribute()
    {
        if ($this->buytotal <= 0) {
            return 0;
        }
        return round(($this->buytotal - $this->buyhave) / $this->buytotal * 100);
    }
    
    public function canBuy()
    {
        if ($this->status != self::STATUS_ONGOING) {
            return false;
        }
        $now = time() * 1000;
        if ($this->endtime <= $now) {
            return false;
        }
        if ($this->buyhave <= 0) {
            return false;
        }
        return true;
    }
}
