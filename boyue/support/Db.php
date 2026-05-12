<?php
namespace support;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Class Db
 * @package support
 * 
 * Strings methods
 * @method static \Illuminate\Database\Query\Builder table(string $table)
 * @method static \Illuminate\Database\Schema\Builder schema()
 * @method static \Illuminate\Database\Connection connection(string $name = null)
 */
class Db
{
    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return Capsule::$name(...$arguments);
    }
}

// Made with Bob
