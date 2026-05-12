<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;

class Lottery extends Model
{
    protected $table = 'lotteries';
    protected $primaryKey = 'id';
    public $timestamps = true;
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    protected $fillable = [
        'key', 'title', 'type', 'is_open', 'is_system',
        'period_count', 'period_interval_sec', 'close_time1', 'close_time2',
        'list_order', 'settings'
    ];
    
    protected $casts = [
        'settings' => 'array',
        'is_open' => 'boolean',
        'is_system' => 'boolean',
    ];
    
    
    public function issues()
    {
        return $this->hasMany(LotteryIssue::class, 'lottery_id');
    }
    
    
    public function results()
    {
        return $this->hasMany(LotteryResult::class, 'lottery_id');
    }
}

