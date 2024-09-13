<?php

namespace Symphograph\Bicycle\DTO;


use PDO;
use PDOException;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Errors\DelRestrictErr;
use Symphograph\Bicycle\PDO\DB;
use Symphograph\Bicycle\PDO\PutMode;
use Symphograph\Bicycle\SQL\SQLBuilder;

trait DTOTrait
{
    use BindTrait;

    const int maxId = 9223372036854775807;

    public static function byId(int $id): static|false
    {
        $tableName = self::tableName;
        $colId = self::getColId();

        $sql = "select * from $tableName where $colId = :$colId";
        $params = [$colId => $id];
        $qwe = DB::qwe($sql, $params);
        return DB::fetchClass($qwe, static::class);
    }

    public static function delById(int $id): void
    {
        $tableName = self::tableName;
        $colId = self::getColId();

        $sql = "delete from $tableName where $colId = :$colId";
        $params = [$colId => $id];

        try{
            DB::qwe($sql, $params);
        } catch (PDOException $e) {
            if(str_contains($e->getMessage(),'ON DELETE RESTRICT') || str_contains($e->getMessage(),'foreign key constraint')){
                throw new DelRestrictErr($e->getMessage());
            }
            throw $e;
        }

    }

    public function del(): void
    {
        if(method_exists(static::class, 'beforeDel')){
            $this->beforeDel();
        }

        $colId = self::getColId();
        self::delById($this->$colId);

        if(method_exists(static::class, 'afterDel')){
            $this->afterDel();
        }
    }

    public static function byProp(string $propName, int|float|string $propValue): self|bool
    {
        $tableName = self::tableName;

        $sql = "select * from $tableName where $propName = :propValue";
        $params = ['propValue' => $propValue];
        $qwe = DB::qwe($sql, $params);

        $result = $qwe->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, self::class);
        if(empty($result)) {
            return false;
        }
        if(count($result) > 1) {
            throw new AppErr("$propName is not unique in table $tableName");
        }
        return $result[0];
    }

    public static function byAccountId(int $accountId): self|bool
    {
        $tableName = self::tableName;

        $sql = "select * from $tableName where accountId = :accountId";
        $params = ['accountId'=> $accountId];
        $qwe = DB::qwe($sql, $params);
        return $qwe->fetchObject(self::class);
    }

    public function putToDB(PutMode $mode = PutMode::safeReplace): void
    {
        $this->putDTOToDB($mode);
    }

    protected function putDTOToDB(PutMode $mode = PutMode::safeReplace): void
    {
        if(method_exists(self::class, 'beforePut')){
            $this->beforePut();
        }

        $mode->execute(self::tableName, $this->getAllProps());

        if(method_exists(self::class, 'afterPut')){
            $this->afterPut();
        }
    }

    /**
     * @return int[]
     */
    public static function getIdList(int $startId = 0, string $orderBy = 'id', ?int $limit = 1000000000000): array
    {
        $tableName = self::tableName;
        $orderBy = self::orderBy($orderBy);

        $qwe = DB::qwe("
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
        /** @noinspection PhpUndefinedFunctionInspection */
        $qwe = qwe($sql);
        return $qwe->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function listByIds(array $ids): array
    {
        $tableName = self::tableName;
        $ids = implode(',', $ids);
        $sql = "select * from $tableName where id in (:ids) order by id";
        /** @noinspection PhpUndefinedFunctionInspection */
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

    public function getDtoProps(): array
    {
        $props = [];
        foreach (get_object_vars($this) as $key => $value) {
            // Проверяем, что свойство определено в классе DTO
            if (property_exists(self::class, $key)) {
                $props[$key] = $value;
            }
        }
        return $props;
    }

    public static function createTable(): void
    {
        $sql = SQLBuilder::createByClass(self::class);
        DB::qwe($sql);
    }

    public static function getClassName(): string
    {
        return static::class;
    }

    public static function getSelectSQL(array $columns = []): string
    {
        $tableName = self::tableName;
        if(empty($columns)) {
            return "select * from $tableName ";
        }
        $sql = "select ";
        $sql .= implode(", ", $columns);
        $sql .= " from $tableName ";
        return $sql;
    }
}