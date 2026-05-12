<?php



namespace support;

use Predis\Client;

class Redis
{
    
    protected static array $instances = [];

    
    public static function connection(string $name = 'default'): Client
    {
        if (!isset(static::$instances[$name])) {
            $config = config('redis.' . $name);
            
            if (!$config) {
                throw new \RuntimeException("Redis配置 [{$name}] 不存在");
            }

            $parameters = [
                'scheme'   => 'tcp',
                'host'     => $config['host'] ?? '127.0.0.1',
                'port'     => $config['port'] ?? 6379,
                'database' => $config['database'] ?? 0,
                'timeout'  => $config['timeout'] ?? 2.0,
            ];

            
            if (!empty($config['password'])) {
                $parameters['password'] = $config['password'];
            }

            static::$instances[$name] = new Client($parameters);
        }

        return static::$instances[$name];
    }

    
    public static function instance(): Client
    {
        return static::connection('default');
    }

    
    public static function __callStatic(string $method, array $args)
    {
        return static::instance()->{$method}(...$args);
    }

    
    public static function disconnect(): void
    {
        foreach (static::$instances as $instance) {
            $instance->disconnect();
        }
        static::$instances = [];
    }
}
