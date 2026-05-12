<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'order_no', 'user_id', 'lottery_id', 'lottery_name',
        'type_id', 'play_id', 'play_title', 'expect', 'unit',
        'bets', 'multiple', 'code', 'amount', 'bonus', 'status',
        'placed_at', 'settled_at'
    ];
    
    protected $casts = [
        'amount' => 'decimal:4',
        'bonus' => 'decimal:4',
    ];
    
    public function lottery()
    {
        return $this->belongsTo(Lottery::class, 'lottery_id');
    }
    
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
