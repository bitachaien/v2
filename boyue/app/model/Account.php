<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'accounts';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    protected $fillable = [
        'user_id', 'balance', 'frozen', 'version'
    ];
    
    protected $casts = [
        'balance' => 'decimal:4',
        'frozen' => 'decimal:4',
    ];
}
