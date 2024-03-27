<?php

namespace Symphograph\Bicycle\DTO;


use PDO;

use Symphograph\Bicycle\PDO\DB;

abstract class AbstractList implements ListITF, \Iterator
{
    protected int $batchSize = 1000;

    public function __construct(
        protected array $list = [],
        public int      $position = 0
    )
    {
    }

    public static function bySql(string $sql, array $params = []): static
    {
        $List = new static();
        $className = static::getItemClass();
        $qwe = DB::qwe($sql, $params);
        $List->list = $qwe->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $className) ?: [];
        return $List;
    }

    public static function byJoinSql(string $sql, array $params = []): static
    {
        $List = new static();
        $className = static::getItemClass();
        $qwe = DB::qwe($sql, $params);
        $rows = $qwe->fetchAll();
        foreach ($rows as $item) {
            $List->list[] = $className::byJoin($item);
        }
        return $List;
    }

    public static function byBind(array $data): static
    {
        $className = static::getItemClass();
        $list = [];
        foreach ($data as $item) {
            $list[] = $className::byBind($item);
        }
        return new static($list);
    }

    public function isEmpty(): bool
    {
        return empty($this->list);
    }

    /**
     * Количество элементов в порции при вставке в БД
     * @return int
     */
    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * Количество элементов в порции при вставке в БД
     * @param int $batchSize
     * @return static
     */
    public function setBatchSize(int $batchSize): static
    {
        $this->batchSize = $batchSize;
        return $this;
    }

    public function getList(): array
    {
        return $this->list;
    }

    public function getProps(): array
    {
        $rows = [];
        foreach ($this->list as $item) {
            $rows[] = $item->getAllProps();
        }
        return $rows;
    }

    public function setList(array $list): static
    {
        $this->list = $list;
        return $this;
    }

    public function initData(): void
    {
        foreach ($this->list as $object) {
            $object->initData();
        }
    }

    /**
     * @return int[]
     */
    public function getIds(): array
    {
        $ids = [];
        foreach ($this->list as $el) {
            $ids[] = $el->id;
        }
        return $ids;
    }


    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->list[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->list[$this->position]);
    }

    public function putToDB(): void
    {
        if(method_exists(static::class, 'beforePut')){
            $this->beforePut();
        }

        $rows = $this->getProps();
        $className = static::getItemClass();

        $cnt = count($rows);
        for ($i = 0; $i < $cnt; $i += $this->batchSize) {
            $batchRows = array_slice($rows, $i, $this->batchSize);  // Получение порции строк
            DB::replaceRows($className::tableName, $batchRows);     // Вставка порции строк
        }

        if(method_exists(static::class, 'afterPut')){
            $this->afterPut();
        }
    }
}