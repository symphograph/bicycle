<?php

namespace Symphograph\Bicycle\Repo\Redis;

use ReflectionClass;
use Symphograph\Bicycle\DTO\BindITF;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Repo\HasRepoITF;

/**
 * @template T of BindITF
 */
abstract class SimpleRedisRepo
{
    protected string $prefix;
    protected RedisTTL $ttl = RedisTTL::_1day;

    protected string $paramIndexPrefix = 'parIdx:';

    /**
     * @param string|null $prefix - префикс для ключей (по умолчанию имя класса)
     * @throws \ReflectionException
     */
    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix ?? strtolower(new ReflectionClass(static::class())->getShortName()) . ':';
    }

    /**
     * @return class-string<T>
     */
    public static function class(): string
    {
        return '';
    }

    protected function key(int $id): string
    {
        return $this->prefix . $id;
    }

    protected function paramKey(array $params): string
    {
        ksort($params);
        $hash = md5(serialize($params));
        return $this->paramIndexPrefix . $this->prefix . $hash;
    }

    public function idByParams($params): string
    {
        //todo
    }

    /**
     * @param int[] $ids
     * @return string[]
     */
    public function keys(array $ids): array
    {
        return array_map(fn($id) => $this->key($id), $ids);
    }

    /**
     * Получить объект по ID
     * @return T|null
     */
    public function get(int $id): ?object
    {
        $key = $this->key($id);
        $data = RedisDB::get($key);

        return $data;
    }

    /**
     * Сохранить объект в Redis
     * @param T $obj
     */
    public function set(object $obj): bool
    {
        $class = static::class();
        if (!($obj instanceof $class)) {
            throw new AppErr('Invalid object type in redis');
        }

        $key = $this->key($obj->id);
        $data = $obj;

        $result = RedisDB::set($key, $data, $this->ttl);
        $this->setParamIndexes($obj);

        return $result;
    }

    protected function setParamIndexes(object $obj): void
    {
        $paramSets = $this->getParamSets($obj);

        foreach ($paramSets as $params) {
            $paramKey = $this->paramKey($params);
            RedisDB::set($paramKey, $obj->id, $this->ttl);
        }
    }

    /**
     * Получить объект по параметрам
     * @param array $params
     * @return T|null
     */
    public function byParams(array $params): ?object
    {
        $paramKey = $this->paramKey($params);
        $id = RedisDB::get($paramKey);

        if (!$id) {
            return null;
        }

        return $this->get($id);
    }

    /**
     * Получить все наборы параметров для объекта
     * Должен быть реализован в дочерних классах
     *
     * @param T $obj
     * @return array<array<string, mixed>>
     */
    protected function getParamSets(object $obj): array
    {
        return [];
    }

    /**
     * Удалить параметрические индексы для объекта
     * @param T $obj
     */
    protected function deleteParamIndexes(object $obj): void
    {
        $paramSets = $this->getParamSets($obj);

        foreach ($paramSets as $params) {
            $paramKey = $this->paramKey($params);
            RedisDB::del($paramKey);
        }
    }

    public function del(int $id): void
    {
        // Сначала получаем объект, чтобы удалить его параметрические индексы
        $obj = $this->get($id);
        if ($obj) {
            $this->deleteParamIndexes($obj);
        }
        RedisDB::del($this->key($id));
    }

    /**
     * Удаляет все объекты
     * @return void
     */
    public function delAll(): void
    {
        $keys = RedisDB::keys($this->prefix . '*');
        if (!empty($keys)) {
            RedisDB::del($keys);
        }
    }

    public function setTtl(RedisTTL $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function getTTL(): RedisTTL
    {
        return $this->ttl;
    }

    public function renewTtl(int $id, ?TTLMode $mode = null): bool
    {
        return RedisDB::setTTL($this->key($id), $this->ttl, $mode);
    }

    public function getTtlForId(int $id): ?int
    {
        return RedisDB::getTTL($this->key($id));
    }
}
//-----------------------------------------------------------