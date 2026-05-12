<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;

class LotteryResult extends Model
{
    protected $table = 'lottery_results';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'lottery_id', 'lottery_key', 'title', 'expect', 'open_code',
        'source_code', 'source', 'is_drawn', 'open_time'
    ];
    
    protected $casts = [
        'is_drawn' => 'boolean',
    ];
    
    
    public function lottery()
    {
        return $this->belongsTo(Lottery::class, 'lottery_id');
    }
}

