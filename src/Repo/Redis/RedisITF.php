<?php

namespace Symphograph\Bicycle\Repo\Redis;


use Symphograph\Bicycle\DTO\BindITF;

/**
 * @template T of BindITF
 */
interface RedisITF
{
    /**
     * @return T|null
     */
    public function get(int $id): ?object;

    /**
     * @param T $dto
     */
    public function set(object $obj): bool;

    public function del(int $id): void;

    /**
     * Возвращает все объекты. Если реализация не поддерживает списки — возвращает [].
     *
     * @return T[]
     */
    public function all(): array;

    /**
     * @param T[] $list
     */
    public function setAll(array $list): bool;

    /**
     * Очистить все объекты из кэша (по необходимости).
     */
    public function delAll(): void;
}