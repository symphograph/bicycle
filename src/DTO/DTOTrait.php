<?php

namespace Symphograph\Bicycle\DTO;


use PDO;
use Symphograph\Bicycle\DB;
use Symphograph\Bicycle\SQL\SQLBuilder;

trait DTOTrait
{
    public static function byId(int $id): self|bool
    {
        $tableName = self::tableName;
        $colId = self::getColId();

        $qwe = qwe("select * from $tableName where $colId = :$colId", [$colId => $id]);
        return $qwe->fetchObject(self::class);
    }

    public static function delFromDB(int $id): self|bool
    {
        $tableName = self::tableName;
        $colId = self::getColId();
        qwe("delete from $tableName where $colId = :$colId", [$colId => $id]);
    }

    public static function byAccountId(int $accountId): self|bool
    {
        $tableName = self::tableName;
        $qwe = qwe("select * from $tableName where accountId = :accountId", ['accountId'=> $accountId]);
        return $qwe->fetchObject(self::class);
    }

    public function putToDB(): void
    {
        $params = DB::initParams($this);
        DB::replace(self::tableName, $params);
    }

    /**
     * @return int[]
     */
    public static function getIdList(int $startId = 0, string $orderBy = 'id', ?int $limit = 1000000000000): array
    {
        $tableName = self::tableName;
        $orderBy = self::orderBy($orderBy);

        $qwe = qwe("
            select id from $tableName 
            where id >= :startId 
            order by $orderBy
            limit :limit",
            ['startId' => $startId, 'limit' => $limit]
        );
        return $qwe->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function orderBy(string $orderBy): string
    {
        return SQLBuilder::orderBy(self::class, $orderBy);
    }

    /**
     * @return self[]
     */
    public static function listBySQL(string $sql): array
    {
        $qwe = qwe($sql);
        return $qwe->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function listByIds(array $ids): array
    {
        $tableName = self::tableName;
        $ids = implode(',', $ids);
        $sql = "select * from $tableName where id in (:ids) order by id";
        $qwe = qwe($sql,['ids' => $ids]);
        return $qwe->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function getColId(): string
    {
        $className = self::class;
        if(defined("$className::colId")){
            return self::colId;
        }
        return 'id';
    }
}