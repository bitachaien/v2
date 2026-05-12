<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    const UPDATED_AT = 'updated_at';
    
    protected $fillable = ['name', 'value'];
    
    protected $casts = [
        'value' => 'array',
    ];
}

