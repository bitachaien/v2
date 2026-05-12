<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    protected $fillable = [
        'order_id', 'line_no', 'code', 'bets', 'multiple',
        'amount', 'bonus', 'status'
    ];
    
    protected $casts = [
        'amount' => 'decimal:4',
        'bonus' => 'decimal:4',
    ];
    
    
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

