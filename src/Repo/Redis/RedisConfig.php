<?php

namespace Symphograph\Bicycle\Repo\Redis;

use Symphograph\Bicycle\Env\Env;

class RedisConfig
{
    public function __construct(
        public string  $host = '127.0.0.1',
        public int     $port = 6379,
        public int     $database = 0,
        public float   $timeout = 0.0,
        public ?string $auth = null,
    ){}

    /**
     * Создать конфиг из переменных окружения
     */
    public static function fromEnv(): self
    {
        return Env::redis();
    }
}