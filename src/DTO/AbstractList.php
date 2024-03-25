<?php

namespace Symphograph\Bicycle\DTO;


use PDO;

use Symphograph\Bicycle\PDO\DB;

abstract class AbstractList implements ListITF, \Iterator
{

    public function __construct(
        protected array $list = [],
        public int $position = 0
    ){}

    public function getList(): array
    {
        return $this->list;
    }

    public function setList(array $list): static
    {
        $this->list = $list;
        return $this;
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
}