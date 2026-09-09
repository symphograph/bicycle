<?php

namespace Symphograph\Bicycle\Repo\Redis;


use Symphograph\Bicycle\DTO\BindITF;

/**
 * Пустая реализация Redis-хранилища.
 * Используется, если Redis не инициализирован.
 *
 * @template T of BindITF
 */
class NullRedisRepo extends SimpleRedisRepo
{
    /**
     * @param class-string<T>|null $className
     * @param string|null $prefix
     */
    public function __construct(?string $className = null, ?string $prefix = null)
    {
        parent::__construct(
            $className ?? BindITF::class,
            $prefix ?? 'null'
        );
    }

    /** @inheritdoc */
    public function set(object $obj): bool
    {
        return true;
    }

    /** @inheritdoc */
    public function get(string|int $id): ?BindITF
    {
        return null;
    }

    /** @inheritdoc */
    public function del(int $id): void
    {
        // Ничего не делаем
    }

    public function delAll(): void
    {
        // Ничего не делаем
    }

    public function setTtl(RedisTTL $ttl): void
    {
        // Ничего не делаем
    }

    /** @inheritdoc */
    public function keys(array $ids): array
    {
        return [];
    }

    /** @inheritdoc */
    public function setAll(array $objects): bool
    {
        return true;
    }

    public function byParams(array $params): ?BindITF
    {
        return null;
    }

    /** @inheritdoc */
    public function clearAll(): void
    {
        // Ничего не делаем
    }

    public function renewTtl(int $id, ?TTLMode $mode = null): bool
    {
        // Ничего не делаем
    }

    public function getTtlForId(int $id): ?int
    {
        // Ничего не делаем
    }
}
//-----------------------------------------------------------