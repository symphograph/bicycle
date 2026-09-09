<?php

namespace Symphograph\Bicycle\Repo\MemoRepo;

use Symphograph\Bicycle\DTO\BindITF;
use Symphograph\Bicycle\Repo\HasRepoITF;
use Symphograph\Bicycle\Repo\RepoITF;

/**
 * @template T of HasRepoITF
 * @mixin BindITF
 */
trait MemoTrait
{
    /** @var array<int, T> */
    protected static array $memo = [];
    protected static array $paramIndex = [];

    private static bool $isWarmed = false;

    protected static function ensureWarmed(): void
    {
        if (!self::$isWarmed) {
            static::warmUpMemo();
            self::$isWarmed = true;
        }
    }

    public static function warmUpMemo(): void
    {
        $all = static::repo()->all();
        foreach ($all as $item) {
            static::$memo[$item->id] = $item;
        }

        static::afterWarmUp();
    }

    /**
     * Получает объект по ID из памяти или БД
     * @return T|null
     */
    public static function byIdFromMemo(int $id, bool $required = true): ?object
    {
        self::ensureWarmed();

        // Проверяем в памяти
        if (isset(static::$memo[$id])) {
            return static::$memo[$id];
        }

        // Если нет в памяти, загружаем из
        $obj = static::repo()->byId($id, $required);
        if ($obj) {
            static::$memo[$id] = $obj;
        }

        return $obj;
    }

    public static function byParamsFromMemo(array $params): ?static
    {
        static::ensureWarmed();
        $key = serialize($params);
        $id = static::$paramIndex[$key] ?? null;
        if($id === null) return null;
        return static::byIdFromMemo($id);
    }

    protected function paramsForKey(): array
    {
        return [];
    }

    protected static function warmIndex(): void
    {
        foreach (self::$memo as $item) {
            $key = $item->paramsForKey();
            $key = serialize($key);
            self::$paramIndex[$key] = $item->id;
        }
    }

    protected static function afterWarmUp(): void
    {
        // Можно переопределить в дочерних классах
    }

    /** @return RepoITF<static> */
    abstract public static function repo(): RepoITF;

    public static function clearMemo(): void
    {
        static::$memo = [];
        static::$paramIndex = [];
    }
}