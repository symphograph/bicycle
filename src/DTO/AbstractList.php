<?php

namespace Symphograph\Bicycle\DTO;



use PDO;

use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Helpers\Arr;
use Symphograph\Bicycle\PDO\DB;
use Symphograph\Bicycle\SQL\SQLBuilder;

abstract class AbstractList implements ListITF, \Iterator
{
    protected const int MaxLimit = 1000000;

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

    public static function doubles(string $colName): static
    {
        $className = static::getItemClass();
        $tableName = $className::tableName;
        $sql = SQLBuilder::doubles($tableName, $colName);
        return static::bySql($sql);
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
            $rows[] = $item->getDtoProps();
        }
        return $rows;
    }

    public function setList(array $list): static
    {
        $this->list = $list;
        return $this;
    }

    public function initData(): static
    {
        foreach ($this->list as $object) {
            $object->initData();
        }
        return $this;
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

    public function sortByCol(array $args = ['votes' => 'desc']): static
    {
        $this->list = Arr::sortMultiArrayByProp($this->list, $args);
        return $this;
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

       foreach ($this->list as $object) {
           $object->putToDB();
       }

        if(method_exists(static::class, 'afterPut')){
            $this->afterPut();
        }
    }

    public function putBatchToDB(): void
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

    public function filter(string $colName, float|int|string $colValue): static
    {
        $this->list = Arr::filter($this->list, $colName, $colValue);
        return $this;
    }
    
    protected static function getTableName(): string
    {
        $className = static::getItemClass();
        return $className::tableName;
    }

    protected static function orderBy(?string $orderBy = null): string
    {
        if(empty($orderBy)) return '';
        $className = static::getItemClass();
        return ' order by ' . SQLBuilder::orderBy($className, $orderBy);
    }

    protected static function limit(?int $limit = null): string
    {
        return empty($limit) ? '' : ' limit ' . $limit;
    }

    protected static function getSelectSql(array $cols): string
    {
        $className = static::getItemClass();
        if (method_exists($className, 'getSelectSql')) {
            return $className::getSelectSql($cols);
        }
        throw new AppErr('method getSelectSql does not exist');
    }

    protected static function sql(string $sql = '', ?string $orderBy = null, ?int $limit = null, array $cols = []): string
    {
        if(!str_starts_with(strtolower($sql), 'select ')){
            $sql = self::getSelectSql($cols);
        }

        if(!empty($orderBy)){
            $sql .= self::orderBy($orderBy);
        }

        if(!empty($limit)){
            $sql .= self::limit($limit);
        }
        return $sql;
    }

    public static function getMaxId(): int
    {
        $className = static::getItemClass();
        return $className::maxId;
    }
}