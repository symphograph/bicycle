<?php

namespace Symphograph\Bicycle\DTO;

use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\PDO\DB;

class SortManager
{
    private string $tableName;
    private ?string $colName;
    private ?int $colVal;
    private string $colIdName = 'id';

    public function __construct(
        string $tableName,
        ?string $colName = null,
        ?int $colVal = null,
        string $colIdName = 'id'
    )
    {
        $this->tableName = $tableName;
        $this->colName = $colName;
        $this->colVal = $colVal;
        if(!empty($colName) && empty($colVal)) {
            throw new AppErr("Col Value can't be empty");
        }
        $this->colIdName = $colIdName;

    }

    public function moveUp(int $id, int $sortVal): void
    {
        $this->swapWithNeighbor($id, $sortVal, 'up');
    }

    public function moveDown(int $id, int $sortVal): void
    {
        $this->swapWithNeighbor($id, $sortVal, 'down');
    }

    private function swapWithNeighbor(int $id, int $sortVal, string $direction): void
    {
        if ($sortVal === 0) {
            $this->reorder();
            return;
            //throw new AppErr("Neighbor sort can't be zero");
        }
        // Определяем предыдущий или следующий элемент
        $neighbor = $direction === 'up'
            ? $this->getPrev($sortVal)
            : $this->getNext($sortVal);

        if (!$neighbor) {
            $this->reorder();
            return;
        }


        DB::pdo()->beginTransaction();

        // Увеличиваем/уменьшаем значения sortVal
        if ($direction === 'up') {
            $this->updateSortVal($neighbor[$this->colIdName], $neighbor['sortVal'] + 1);
            $this->updateSortVal($id, $sortVal - 1);
        } else {
            $this->updateSortVal($neighbor[$this->colIdName], $neighbor['sortVal'] - 1);
            $this->updateSortVal($id, $sortVal + 1);
        }

        // Пересчитываем порядок
        $this->reorder();

        DB::pdo()->commit();
    }

    private function getPrev(int $sortVal): array|false
    {
        $lessSortVal = $sortVal - 1;
        if(empty($this->colName)) {
            $sql = "SELECT * FROM {$this->tableName} WHERE sortVal = :sortVal";
            $params = ['sortVal' => $lessSortVal];
        } elseif($this->colVal !== null) {
            $sql = "
            SELECT * FROM {$this->tableName} 
             WHERE sortVal = :sortVal
             AND {$this->colName} = :colVal";
            $params = ['sortVal' => $lessSortVal, 'colVal' => $this->colVal];
        }

        return DB::qwe($sql, $params)->fetch();
    }

    private function getNext(int $sortVal): array|false
    {
        if(empty($this->colName)) {
            $sql = "SELECT * FROM {$this->tableName} WHERE sortVal = :sortVal";
            $params = ['sortVal' => $sortVal + 1];

        } elseif($this->colVal !== null) {
            $sql = "
            SELECT * FROM {$this->tableName} 
             WHERE sortVal = :sortVal
             AND {$this->colName} = :colVal";
            $params = ['sortVal' => $sortVal + 1, 'colVal' => $this->colVal];
        }
        return DB::qwe($sql, $params)->fetch();

    }

    private function updateSortVal(int $id, int $newSortVal): void
    {
        $sql = "UPDATE {$this->tableName} SET sortVal = :sortVal WHERE {$this->colIdName} = :id";
        DB::qwe($sql, ['sortVal' => $newSortVal, 'id' => $id]);
    }

    public function reorder(): void
    {
        if(empty($this->colName)) {
            $sql = "SELECT {$this->colIdName} FROM {$this->tableName} ORDER BY sortVal";
            $rows = DB::qwe($sql)->fetchAll();
        }elseif($this->colVal !== null){
            $sql = "SELECT {$this->colIdName} FROM {$this->tableName} WHERE {$this->colName} = :colVal ORDER BY sortVal";
            $rows = DB::qwe($sql, ['colVal' => $this->colVal])->fetchAll();
        }

        $sortVal = 1;

        foreach ($rows as $row) {
            $this->updateSortVal($row[$this->colIdName], $sortVal++);
        }
    }
}
