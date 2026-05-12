<?php
/**
 * Database Bootstrap
 * Initialize Illuminate Database Capsule
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Container\Container;

$capsule = new Capsule;

// Get database config
$config = config('database', []);

if (!empty($config)) {
    // Add default connection
    if (isset($config['connections'][$config['default']])) {
        $capsule->addConnection($config['connections'][$config['default']]);
    }
    
    // Add other connections
    foreach ($config['connections'] as $name => $connection) {
        if ($name !== $config['default']) {
            $capsule->addConnection($connection, $name);
        }
    }
    
    // Make this Capsule instance available globally
    $capsule->setAsGlobal();
    
    // Setup Eloquent ORM
    $capsule->bootEloquent();
}

// Made with Bob
