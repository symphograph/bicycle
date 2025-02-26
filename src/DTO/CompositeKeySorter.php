<?php

namespace Symphograph\Bicycle\DTO;

use Symphograph\Bicycle\PDO\DB;

class CompositeKeySorter
{
    private string $tableName;
    private string $col1;
    private string $col2;

    public function __construct(
        string $tableName,
        string $col1, // Первый столбец составного ключа
        string $col2, // Второй столбец составного ключа
    )
    {
        $this->tableName = $tableName;
        $this->col1 = $col1;
        $this->col2 = $col2;
    }

    public function moveUp(int $val1, int $val2, int $sortVal): void
    {
        $this->swapWithNeighbor($val1, $val2, $sortVal, 'up');
    }

    public function moveDown(int $val1, int $val2, int $sortVal): void
    {
        $this->swapWithNeighbor($val1, $val2, $sortVal, 'down');
    }

    private function swapWithNeighbor(int $val1, int $val2, int $sortVal, string $direction): void
    {
        $neighbor = $direction === 'up'
            ? $this->getPrev($sortVal, $val1)
            : $this->getNext($sortVal, $val1);

        if (!$neighbor) return;

        DB::pdo()->beginTransaction();

        // Меняем значения sortVal
        $this->updateSortVal($neighbor[$this->col2], $neighbor['sortVal'] + ($direction === 'up' ? 1 : -1), $val1);
        $this->updateSortVal($val2, $sortVal + ($direction === 'up' ? -1 : 1), $val1);

        $this->reorder($val1);

        DB::pdo()->commit();
    }

    private function getPrev(int $sortVal, int $val1): array|false
    {
        $sql = "SELECT * FROM $this->tableName WHERE sortVal = :sortVal AND $this->col1 = :val1";
        $params = ['sortVal' => $sortVal - 1, 'val1' => $val1];
        return DB::qwe($sql, $params)->fetch();
    }

    private function getNext(int $sortVal, int $val1): array|false
    {
        $sql = "SELECT * FROM $this->tableName WHERE sortVal = :sortVal AND $this->col1 = :val1";
        $params = ['sortVal' => $sortVal + 1, 'val1' => $val1];
        return DB::qwe($sql, $params)->fetch();
    }

    private function updateSortVal(int $val2, int $newSortVal, int $val1): void
    {
        $sql = "UPDATE $this->tableName SET sortVal = :sortVal WHERE $this->col1 = :val1 AND $this->col2 = :val2";
        DB::qwe($sql, ['sortVal' => $newSortVal, 'val1' => $val1, 'val2' => $val2]);
    }

    public function reorder(int $val1): void
    {
        $sql = "SELECT $this->col2 FROM $this->tableName WHERE $this->col1 = :val1 ORDER BY sortVal";
        $rows = DB::qwe($sql, ['val1' => $val1])->fetchAll();

        $sortVal = 1;
        foreach ($rows as $row) {
            $this->updateSortVal($row[$this->col2], $sortVal++, $val1);
        }
    }
}