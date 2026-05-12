<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;

class LotteryIssue extends Model
{
    protected $table = 'lottery_issues';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    protected $fillable = [
        'lottery_id', 'expect', 'start_time', 'end_time',
        'close_ahead_sec', 'status'
    ];
    
    
    public function lottery()
    {
        return $this->belongsTo(Lottery::class, 'lottery_id');
    }
}

