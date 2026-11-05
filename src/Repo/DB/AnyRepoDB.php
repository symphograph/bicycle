<?php

namespace Symphograph\Bicycle\Repo\DB;

use InvalidArgumentException;
use PDO;
use ReflectionClass;
use Symphograph\Bicycle\DTO\BindITF;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Helpers\DTime\DTF;
use Symphograph\Bicycle\PDO\DB;

/**@template T of BindITF */
abstract class AnyRepoDB implements DBRepoITF
{
    const string storeTable = '';
    const string viewTable = '';

    public function classShort(): string
    {
        return new ReflectionClass(static::class())->getShortName();
    }

    /**
     * @return class-string<T>
     */
    abstract public static function class(): string;

    /**
     * @return T|null
     */
    public function byId(int $id): ?BindITF
    {
        $table = static::viewTable;
        $sql = "SELECT * FROM $table WHERE id = :id";
        $params = ['id' => $id];
        $row = DB::qwe($sql, $params);
        if (!$row) return null;

        $data = $row->fetch(PDO::FETCH_ASSOC);
        return $data ? static::class()::byArr($data) : null;

    }

    /**
     * Находит одну запись по набору параметров
     *
     * @param array<string, mixed> $params Ассоциативный массив [поле => значение]
     * @return T|null
     */
    public function byParams(array $params): ?BindITF
    {
        if (empty($params)) {
            return null;
        }

        $table = static::viewTable;
        $whereConditions = [];

        foreach (array_keys($params) as $field) {
            $whereConditions[] = "$field = :$field";
        }

        $whereClause = implode(' AND ', $whereConditions);
        $sql = "SELECT * FROM $table WHERE $whereClause LIMIT 1";

        $row = DB::qwe($sql, $params);
        if (!$row) return null;

        $data = $row->fetch(PDO::FETCH_ASSOC);
        return $data ? static::class()::byArr($data) : null;
    }

    /**
     * @return T[]
     */
    public function all(): array
    {
        $table = static::viewTable;
        $sql = "SELECT * FROM $table";
        return $this->listBySQL($sql);
    }

    /**
     * @return T[]
     */
    public function listAll(): array
    {
        return $this->all();
    }

    /**
     * @return int[]
     */
    public function listAllIds(): array
    {
        $table = static::storeTable;
        $sql = "SELECT id FROM $table";
        return $this->listIdsBySQL($sql);
    }

    /**
     * @param string $sql
     * @param array $params
     * @return T[]
     */
    public function listBySQL(string $sql, array $params = []): array
    {
        $stmt = DB::qwe($sql, $params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $stmt ? array_map([static::class(), 'byArr'], $rows) : [];
    }

    /**
     * Выполняет SQL запрос для получения ID
     * @return int[]
     */
    protected function listIdsBySQL(string $sql, array $params = []): array
    {
        $stmt = DB::qwe($sql, $params);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        return $ids ?: [];
    }

    /**
     * @param int[] $ids
     * @return T[]
     */
    public function listByIds(array $ids): array
    {
        if (empty($ids)) return [];
        $table = static::viewTable;
        $in = DB::implodeIntIn($ids);
        $sql = "SELECT * FROM $table WHERE id IN $in";

        return $this->listBySQL($sql);
    }


    public function listUpdatedIds(\DateTime $fromDate): array
    {
        $table = static::viewTable;
        $sql = "SELECT id FROM $table WHERE updatedAt > :updatedAt";
        $params = ['updatedAt' => DTF::dtSqlMicro($fromDate)];
        return $this->listIdsBySQL($sql, $params);
    }

    public function listUpdatedPrivateIds(\DateTime $fromDate, int $createdBy): array
    {
        $table = static::viewTable;
        $sql = "SELECT id FROM $table WHERE updatedAt > :updatedAt AND createdBy = :createdBy";
        $params = ['updatedAt' => DTF::dtSqlMicro($fromDate), 'createdBy' => $createdBy];
        return $this->listIdsBySQL($sql, $params);
    }

    /**
     * @param T $data
     */
    public function insert(BindITF $data, bool $auto = false): void
    {
        if ($auto && !empty($data->id)) {
            $message = "ID must be empty for auto increment. Given $data->id.";
            throw new InvalidArgumentException($message);
        }
        $params = $this->params($data);
        if($auto) unset($params['id']);
        DB::insert(static::storeTable, $this->params($data));
        if (!$auto) return;
        
        $lastId = (int) DB::lastId();
        if(empty($lastId)) throw new AppErr("Last id is empty after insert");
        $data->id = $lastId; //pdo
    }

    /**
     * @param T $data
     */
    public function update(BindITF $data): void
    {
        DB::replace(static::storeTable, $this->params($data));
    }

    public function delete(int $id): void
    {
        $table = static::storeTable;
        $sql = "DELETE FROM $table WHERE id = :id";
        $params = ['id' => $id];
        DB::qwe($sql, $params);
    }

    /**
     * @param T $data
     * @return array
     */
    protected function params(BindITF $data): array
    {
        return $data->toArray();
    }

    public function exists(int $id): bool
    {
        $table = static::storeTable;
        $sql = "SELECT id FROM $table WHERE id = :id";
        $stmt = DB::qwe($sql, ['id' => $id]);
        return $stmt?->rowCount() ?? 0 > 0;
    }

    public function pdo(): \PDO
    {
        return DB::pdo();
    }

    public function clearTable(): void
    {

    }

    public function dropTable(): void
    {
        $tableName = static::storeTable;
        $sql = "DROP TABLE IF EXISTS $tableName";
        DB::qwe($sql);
    }

    public function lockById(int $id): bool
    {
        $table = static::storeTable;
        $stmt = DB::qwe(
            "SELECT id FROM $table WHERE id = :id FOR UPDATE",
            ['id' => $id]
        );
        return $stmt->rowCount() > 0;
    }
}
//-------------------------------------------------------------