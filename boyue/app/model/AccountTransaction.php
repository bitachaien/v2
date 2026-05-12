<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{
    protected $table = 'account_transactions';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'user_id', 'type', 'amount', 'balance_before', 'balance_after',
        'ref_type', 'ref_id', 'remark'
    ];
    
    protected $casts = [
        'amount' => 'decimal:4',
        'balance_before' => 'decimal:4',
        'balance_after' => 'decimal:4',
    ];
}

