<?php

namespace Symphograph\Bicycle\DTO;

use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\PDO\DB;

class SortManager
{
    private string $tableName;
    private ?string $colName;
    private ?int $colVal;

    public function __construct(string $tableName, ?string $colName = null, ?int $colVal = null)
    {
        $this->tableName = $tableName;
        $this->colName = $colName;
        $this->colVal = $colVal;
        if(!empty($colName) && empty($colVal)) {
            throw new AppErr("Col Value can't be empty");
        }
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
        if(empty($this->colName)) {
            $sql = "SELECT * FROM {$this->tableName} WHERE sortVal = :sortVal";
            $params = ['sortVal' => $sortVal - 1];
        } elseif(!empty($this->colVal)) {
            $sql = "
            SELECT * FROM {$this->tableName} 
             WHERE sortVal = :sortVal
             AND {$this->colName} = :colVal";
            $params = ['sortVal' => $sortVal - 1, 'colVal' => $this->colVal];
        }
        return DB::qwe($sql, $params)->fetch();
    }

    private function getNext(int $sortVal): array|false
    {
        if(empty($this->colName)) {
            $sql = "SELECT * FROM {$this->tableName} WHERE sortVal = :sortVal";
            $params = ['sortVal' => $sortVal + 1];

        } elseif(!empty($this->colVal)) {
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
        $sql = "UPDATE {$this->tableName} SET sortVal = :sortVal WHERE id = :id";
        DB::qwe($sql, ['sortVal' => $newSortVal, 'id' => $id]);
    }

    private function reorder(): void
    {
        if(empty($this->colName)) {
            $sql = "SELECT id FROM {$this->tableName} ORDER BY sortVal";
            $rows = DB::qwe($sql)->fetchAll();
        }elseif(!empty($this->colVal)){
            $sql = "SELECT id FROM {$this->tableName} WHERE {$this->colName} = :colVal ORDER BY sortVal";
            $rows = DB::qwe($sql, ['colVal' => $this->colVal])->fetchAll();
        }


        $sortVal = 1;

        foreach ($rows as $row) {
            $this->updateSortVal($row['id'], $sortVal++);
            //$sql = "UPDATE {$this->tableName} SET sortVal = :sortVal WHERE id = :id";
            //DB::qwe($sql, ['sortVal' => $sortVal++, 'id' => $row['id']]);
        }
    }
}
