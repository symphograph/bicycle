<?php

namespace Symphograph\Bicycle\DTO;


use PDO;

use Symphograph\Bicycle\PDO\DB;

abstract class AbstractList implements ListITF
{

    public function __construct(protected array $list = []){}

    public function getList(): array
    {
        return $this->list;
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



}