<?php

namespace app\model;

use support\Model;

class MemberStats extends Model
{
    protected $table = 'caipiao_member_stats';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    public $timestamps = false;
    
    protected $fillable = [
        'uid',
        'level',
        'follower_count',
        'hemai_count',
        'hemai_win_count',
        'last_5_win',
        'total_amount',
        'total_win',
        'updatetime',
    ];
    
    protected $casts = [
        'uid' => 'integer',
        'level' => 'integer',
        'follower_count' => 'integer',
        'hemai_count' => 'integer',
        'hemai_win_count' => 'integer',
        'last_5_win' => 'integer',
        'total_amount' => 'decimal:2',
        'total_win' => 'decimal:2',
        'updatetime' => 'integer',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'uid');
    }
    
    public function getWinRateAttribute()
    {
        if ($this->hemai_count <= 0) {
            return 0;
        }
        return round($this->hemai_win_count / $this->hemai_count * 100, 2);
    }
    
    public function getLevelNameAttribute()
    {
        $map = [
            1 => '新手',
            2 => '初级',
            3 => '中级',
            4 => '高级',
            5 => '专家',
        ];
        return $map[$this->level] ?? '未知';
    }
}
