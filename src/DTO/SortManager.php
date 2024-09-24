<?php

namespace Symphograph\Bicycle\DTO;

use Symphograph\Bicycle\PDO\DB;

class SortManager
{
    private string $tableName;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
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
        // Определяем предыдущий или следующий элемент
        $neighbor = $direction === 'up'
            ? $this->getPrev($sortVal)
            : $this->getNext($sortVal);

        if (!$neighbor) return;

        DB::pdo()->beginTransaction();

        // Увеличиваем/уменьшаем значения sortVal
        if ($direction === 'up') {
            $this->updateSortVal($neighbor['id'], $neighbor['sortVal'] + 1);
            $this->updateSortVal($id, $sortVal - 1);
        } else {
            $this->updateSortVal($neighbor['id'], $neighbor['sortVal'] - 1);
            $this->updateSortVal($id, $sortVal + 1);
        }

        // Пересчитываем порядок
        $this->reorder();

        DB::pdo()->commit();
    }

    private function getPrev(int $sortVal): array|false
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE sortVal = :sortVal";
        return DB::qwe($sql, ['sortVal' => $sortVal - 1])->fetch();
    }

    private function getNext(int $sortVal): array|false
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE sortVal = :sortVal";
        return DB::qwe($sql, ['sortVal' => $sortVal + 1])->fetch();
    }

    private function updateSortVal(int $id, int $newSortVal): void
    {
        $sql = "UPDATE {$this->tableName} SET sortVal = :sortVal WHERE id = :id";
        DB::qwe($sql, ['sortVal' => $newSortVal, 'id' => $id]);
    }

    private function reorder(): void
    {
        $sql = "SELECT id FROM {$this->tableName} ORDER BY sortVal";
        $rows = DB::qwe($sql)->fetchAll();
        $sortVal = 1;

        foreach ($rows as $row) {
            $sql = "UPDATE {$this->tableName} SET sortVal = :sortVal WHERE id = :id";
            DB::qwe($sql, ['sortVal' => $sortVal++, 'id' => $row['id']]);
        }
    }
}
