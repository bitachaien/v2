<?php

namespace support;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Base Model class extending Eloquent Model
 * 
 * This provides a base model class for the application
 * that extends Laravel's Eloquent ORM Model
 */
class Model extends EloquentModel
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'default';
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    
    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';
}

// Made with Bob
