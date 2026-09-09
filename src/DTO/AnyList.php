<?php

namespace Symphograph\Bicycle\DTO;

use DateTime;
use PDO;
use Symphograph\Bicycle\PDO\DB;
use Symphograph\Bicycle\Repo\HasClassITF;
use Symphograph\Bicycle\Repo\HasRepoITF;

/**
 * @template T of HasRepoITF
 */
abstract class AnyList implements HasClassITF
{
    protected int $batchSize = 10;


    /**
     * @param T[] $list
     */
    public function __construct(protected array $list = [])
    {
    }

    public static function all(): static
    {
        $list = static::class()::repo()->all();
        return new static($list);
    }

    /**
     * @return T[]
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @return T[]
     */
    public function randomItems(int $limit): array
    {
        $items = $this->list;
        $total = count($items);

        // Если лимит больше или равен количеству элементов, возвращаем все в случайном порядке
        if ($limit >= $total) {
            shuffle($items);
            return $items;
        }

        // Выбираем случайные уникальные ключи
        $keys = array_rand($items, $limit);

        // Если выбран только один элемент, array_rand вернёт не массив, а скаляр
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        // Собираем результат по выбранным ключам
        $result = [];
        foreach ($keys as $key) {
            $result[] = $items[$key];
        }

        return $result;
    }

    /**
     * @return T[]
     */
    public function map(): array
    {
        $arr = [];
        foreach ($this->list as $item) {
            $arr[$item->id] = $item;
        }
        return $arr;
    }


    /**
     * Вставляет в бд порциями с повторными попытками при deadlock.
     * on duplicate key update
     */
    public function putBatchToDB(): void
    {
        $rows = $this->getRows();
        $tableName = static::storeTable();

        $cnt = count($rows);
        for ($i = 0; $i < $cnt; $i += $this->batchSize) {
            $batchRows = array_slice($rows, $i, $this->batchSize);
            $fn = fn() => DB::replaceRows($tableName, $batchRows);
            DB::executeWithRetry($fn);
        }
    }
    
    protected function getRows(): array
    {
        return array_map(fn($item) => $item->toArray(), $this->list);
    }

    public static function ofUpdated(DateTime $fromDate): static
    {
        $list = static::class()::repo()->listOfUpdated($fromDate);
        return new static($list);
    }

    /**
     * @param string $sql
     * @param array $params
     * @return static
     */
    public static function bySQL(string $sql, array $params = []): static
    {
        $stmt = DB::qwe($sql, $params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $list = $stmt ? array_map([static::class(), 'byArr'], $rows) : [];
        return new static($list);
    }

    public static function storeTable(): string
    {
        return static::class()::repo()->db()::storeTable;
    }
}