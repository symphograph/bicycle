<?php

namespace Symphograph\Bicycle\Repo\Redis;

use ReflectionClass;
use Symphograph\Bicycle\DTO\BindITF;
use Symphograph\Bicycle\Errors\AppErr;

/**
 * Универсальный Redis репозиторий для объектов
 *
 * @template T of BindITF
 */
class EvilRedisRepo implements RedisITF
{
    /** @var class-string<T> */
    protected string $className;

    protected string $prefix;
    protected string $allKey;
    protected ?int   $ttl = null;

    /**
     * @param class-string<T> $className
     * @param string|null $prefix - префикс для ключей (по умолчанию имя класса)
     * @throws \ReflectionException
     */
    public function __construct(string $className, ?string $prefix = null)
    {
        $this->className = $className;
        $this->prefix = $prefix ?? strtolower(new ReflectionClass($className)->getShortName()) . ':';
        $this->allKey = $this->prefix . 'all';
    }

    protected function key(int $id): string
    {
        return $this->prefix . $id;
    }

    /**
     * Получить объект по ID
     * @return T|null
     */
    public function get(int $id): ?object
    {
        $key = $this->key($id);
        $json = RedisDB::get($key);
        return $json ? ($this->className)::byJson($json) : null;
    }

    /**
     * Сохранить объект в Redis
     * @param T $obj
     */
    public function set(object $obj): bool
    {
        if (!($obj instanceof $this->className)) {
            throw new AppErr('Invalid object type in redis');
        }

        $key = $this->key($obj->id);
        $json = $obj->toJson();
        $ttl = $this->ttl ?? 0;

        $script = <<<LUA
            redis.call('SET', KEYS[1], ARGV[1])
            if ARGV[2] ~= '0' then
                redis.call('EXPIRE', KEYS[1], ARGV[2])
            end
            redis.call('SADD', KEYS[2], ARGV[3])
            return 1
        LUA;

        $result = RedisDB::evalScript(
            $script,
            [$key, $this->allKey], // ключи
            [$json, $ttl, $obj->id] // аргументы
        );

        return $result === 1;
    }

    public function all(bool $raw = false): array
    {
        return $raw ? $this->allRawJsons() : $this->allObjects();
    }

    /**
     * Собрать все объекты в массив
     * @return T[]
     */
    private function allObjects(): array
    {
        $ids = $this->getAllIds();
        if (empty($ids)) {
            return [];
        }

        $keys = array_map(fn($id) => $this->key($id), $ids);
        $jsonArr = RedisDB::mget($keys);
        $objects = [];
        foreach ($jsonArr as $json) {
            if ($json) {
                $objects[] = ($this->className)::byJson($json);
            }
        }

        return $objects;
    }

    /**
     * Собрать все объекты в массив JSON строк
     */
    private function allRawJsons(): array
    {
        $ids = $this->getAllIds();
        if (empty($ids)) {
            return [];
        }

        $keys = array_map(fn($id) => $this->key($id), $ids);
        return array_filter(RedisDB::mget($keys));
    }


    /**
     * Сохранить список объектов
     * @param T[] $list
     */
    public function setAll(array $list): bool
    {
        if (empty($list)) return true;

        $keyValues = [];
        $ids = [];

        foreach ($list as $obj) {
            if (!($obj instanceof $this->className)) {
                throw new AppErr('Invalid object type in redis');
            }
            $key = $this->key($obj->id);
            $keyValues[$key] = $obj->toJson();
            $ids[] = $obj->id;
        }

        RedisDB::mset($keyValues);

        if ($this->ttl !== null) {
            foreach (array_keys($keyValues) as $key) {
                RedisDB::setTTL($key, $this->ttl);
            }
        }

        // Удаляем старый set и создаем новый
        RedisDB::del($this->allKey);
        if (!empty($ids)) {
            RedisDB::sAdd($this->allKey, ...$ids);
            if ($this->ttl !== null) {
                RedisDB::setTTL($this->allKey, $this->ttl);
            }
        }

        return true;
    }

    public function del(int $id): void
    {
        RedisDB::del($this->key($id));
        RedisDB::sRem($this->allKey, $id);
    }

    public function delAll(): void
    {
        $ids = $this->getAllIds();
        $keys = array_map(fn($id) => $this->key($id), $ids);
        if (!empty($keys)) RedisDB::delMultiple($keys);
        RedisDB::del($this->allKey);
    }

    public function setTtl(?int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function renewTtl(int $id): bool
    {
        if ($this->ttl === null) return false;
        return RedisDB::setTTL($this->key($id), $this->ttl);
    }

    public function getTtlForId(int $id): ?int
    {
        return RedisDB::getTTL($this->key($id));
    }

    public function setTtlForAll(?int $ttl = null): void
    {
        $this->ttl = $ttl;
        $ids = $this->getAllIds();
        foreach ($ids as $id) {
            RedisDB::setTTL($this->key($id), $ttl ?? 0);
        }
        RedisDB::setTTL($this->allKey, $ttl ?? 0);
    }

    /**
     * Получить массив всех ID
     * @return int[]
     */
    protected function getAllIds(): array
    {
        $ids = RedisDB::sMembers($this->allKey);
        return array_map('intval', $ids);
    }
}