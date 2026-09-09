<?php

namespace Symphograph\Bicycle\Repo;

use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Repo\Redis\RedisDB;

/**
 * Класс для управления кэшированным списком ID в Redis.
 *
 * @template T of object
 */
class ListRepo
{
    /**
     * @param ListEnumITF $listEnum  Определение списка
     * @param object      $db        Репозиторий БД, содержащий методы выборки
     * @param class-string<T> $class Класс объектов списка
     */
    public function __construct(
        protected ListEnumITF $listEnum,
        protected object $db,
        public string $class
    ) {}

    /**
     * Формирует ключ Redis для списка на основе переданных параметров.
     */
    private function key(array $params = []): string
    {
        return $this->listEnum->key($params);
    }

    /**
     * Загружает полные объекты из БД по методу, указанному в enum.
     *
     * @return T[]
     */
    public function fetchObjectsFromDB(array $params = []): array
    {
        $method = $this->listEnum->method();

        if (!method_exists($this->db, $method)) {
            throw new \RuntimeException(
                "Method {$method} does not exist in " . get_class($this->db)
            );
        }

        // Передаём параметры как именованные аргументы
        return $this->db->$method(...$params);
    }

    /**
     * Сохраняет массив ID в Redis Set.
     */
    public function storeList(array $ids, array $params = []): void
    {
        $key = $this->key($params);

        // Удаляем старый ключ в любом случае
        RedisDB::del($key);

        if (empty($ids)) {
            // Для пустых наборов просто не создаем ключ
            // getCachedIds будет возвращать null, что заставит перезагрузить из БД
            return;
        }

        // Используем пайплайн для эффективной вставки
        $pipeline = RedisDB::pipeline();

        // Добавляем элементы пачками по 1000
        $chunks = array_chunk($ids, 1000);
        foreach ($chunks as $chunk) {
            $pipeline->sadd($key, ...$chunk);
        }

        $pipeline->setTTL($key, $this->listEnum->ttl());
        $pipeline->exec();
    }

    /**
     * Пытается получить массив ID списка из Redis.
     * Возвращает null, если ключ отсутствует (в том числе для пустого списка).
     */
    public function getCachedIds(array $params = []): ?array
    {
        $key = $this->key($params);

        // Проверяем существование ключа
        if (!RedisDB::exists($key)) {
            return null;
        }

        // Получаем все элементы множества
        $ids = RedisDB::smembers($key);
        if (!$ids) {
            return null; // набор существует, но пуст – трактуем как отсутствие
        }

        sort($ids);
        return $ids;
    }

    /**
     * Инвалидирует кэш списка для конкретных параметров.
     */
    public function invalidate(array $params = []): void
    {
        $key = $this->key($params);
        RedisDB::del($key);
    }

    /**
     * Инвалидирует все варианты списка (все комбинации параметров).
     */
    public function invalidateAll(): void
    {
        $pattern = $this->listEnum->pattern();

        if ($this->listEnum->propNames()) {
            // Для шаблонов с wildcard используем SCAN
            $keys = RedisDB::keysByPattern($pattern);
        } else {
            // Для точных ключей проверяем существование и удаляем напрямую
            $keys = RedisDB::exists($pattern) ? [$pattern] : [];
        }

        if (!empty($keys)) {
            RedisDB::del($keys);
        }
    }

    /**
     * Возвращает количество элементов в кэшированном списке.
     * null, если кэш отсутствует.
     */
    public function countCached(array $params = []): ?int
    {
        $key = $this->key($params);

        if (!RedisDB::exists($key)) {
            return null;
        }

        return RedisDB::scard($key);
    }

    /**
     * Проверяет наличие ID в кэшированном списке.
     * null, если кэш отсутствует.
     */
    public function isInCachedList(int $itemId, array $params = []): ?bool
    {
        $key = $this->key($params);

        if (!RedisDB::exists($key)) {
            return null;
        }

        return RedisDB::sismember($key, $itemId);
    }

    /**
     * Загружает только ID из БД по методу, указанному в enum.
     *
     * @return int[]
     */
    public function fetchIdsFromDB(array $params = []): array
    {
        $method = $this->listEnum->method() . 'Ids';

        if (!method_exists($this->db, $method)) {
            $msg = "Method {$method} does not exist in " . get_class($this->db);
            throw new AppErr($msg);
        }

        return $this->db->$method(...$params);
    }
}