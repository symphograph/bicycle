<?php

namespace Symphograph\Bicycle\Repo\Redis;

/**
 * Обертка для работы с пайплайном Redis
 */
class PipelineWrapper
{
    private array $commands = [];

    public function __construct()
    {
        RedisDB::client()->multi(\Redis::PIPELINE);
    }

    public function del(string $key): self
    {
        RedisDB::del($key);
        return $this;
    }

    public function sadd(string $key, ...$members): self
    {
        RedisDB::sadd($key, ...$members);
        return $this;
    }

    public function setTTL(string $key, RedisTTL $ttl, ?TTLMode $mode = null): self
    {
        RedisDB::setTTL($key, $ttl, $mode?->value);
        return $this;
    }

    public function srem(string $key, ...$members): self
    {
        RedisDB::srem($key, ...$members);
        return $this;
    }

    /**
     * Выполняет все команды в пайплайне
     * @return array
     */
    public function exec(): array
    {
        return RedisDB::exec();
    }
}