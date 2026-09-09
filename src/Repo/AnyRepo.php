<?php

namespace Symphograph\Bicycle\Repo;

use RuntimeException;
use Symphograph\Bicycle\DTO\BindITF;
use Symphograph\Bicycle\Repo\DB\AnyRepoDB;
use Symphograph\Bicycle\Repo\MemoRepo\AnyRepoMemo;
use Symphograph\Bicycle\Repo\MemoRepo\AnyRepoNullMemo;
use Symphograph\Bicycle\Repo\Redis\NullRedisRepo;
use Symphograph\Bicycle\Repo\Redis\SimpleRedisRepo;

/**
 * @template T of BindITF
 */
abstract class AnyRepo implements RepoITF
{
    /**
     * @param AnyRepoDB<T> $db
     * @param SimpleRedisRepo<T> $redis
     */
    public function __construct(
        protected AnyRepoDB       $db,
        protected SimpleRedisRepo $redis = new NullRedisRepo(),
        protected AnyRepoNullMemo|AnyRepoMemo $memo = new AnyRepoNullMemo(),
    )
    {
    }

    public function redis(): SimpleRedisRepo
    {
        return $this->redis;
    }

    /**
     * @return AnyRepoDB<T>
     */
    public function db(): AnyRepoDB
    {
        return $this->db;
    }


    /**
     * @return class-string<T>
     */
    abstract public static function class(): string;

    /**
     * @return T|null
     */
    public function byId(int $id, bool $required = true): ?BindITF
    {
        $obj = $this->memo->byIdFromMemo($id);
        if($obj) return $obj;

        $obj = $this->redis->get($id);
        if ($obj) return $obj;

        $obj = $this->db->byId($id);
        if (!$obj && $required) {
            $class = $this->db->classShort();
            throw new RuntimeException("Entity $class not found: $id");
        }

        if ($obj) $this->redis->set($obj);

        return $obj;
    }

    /**
     * @return T|null
     */
    public function byParams(array $params): ?BindITF
    {
        $res = $this->memo->byParamsFromMemo($params);
        if($res) return $res;

        $res = $this->redis->byParams($params);
        if($res) return $res;

        $res = $this->db()->byParams($params);
        if($res) $this->redis->set($res);
        return $res;
    }

    /**
     * @param T $data
     * @return T
     */
    public function insertAndTake(object $data, bool $auto = false): BindITF
    {
        $this->insert($data, $auto);
        return $this->db()->byId($data->id);
    }

    /**
     * @param T $data
     */
    public function insert(object $data, bool $auto = false): void
    {
        $this->db->insert($data, $auto);

        if (empty($data->id)) {
            throw new RuntimeException("Entity has empty ID after insert");
        }

        /*
        if (!$this->db->exists($data->id)) {
            throw new RuntimeException("Entity {$data->id} no exist in storeTable");
        }

        $newData = $this->db->byId($data->id);
        if (!$newData) {
            throw new RuntimeException("Entity {$data->id} is empty after insert");
        }
        */

        $this->clearAllRedis();
        $this->memo->clear();
    }

    /**
     * @param T $data
     */
    public function update(BindITF $data): void
    {
        if (empty($data->id)) throw new RuntimeException("Entity has empty ID for update");
        $this->db->update($data);
        $this->clearRedis($data);
        $this->memo->clear();
    }

    public function clearRedisById(int $id): void
    {
        $obj = $this->redis->get($id);
        if($obj) $this->clearOnCRUD($obj);
        $this->redis->del($id);
    }

    public function clearRedis(BindITF $data): void
    {
        $this->redis->del($data->id);
        $this->clearOnCRUD($data);
    }

    public function clearAllRedis(): void
    {
        $this->redis->delAll();
        $this->clearAllLists();
    }

    public function clearAllCaches(): void
    {
        $this->clearAllRedis();
        $this->memo->clear();
    }

    /**
     * Пересоздаст таблицу и очистит кеши.
     */
    public function clearAllStorages(): void
    {
        $this->db->clearTable();
        $this->clearAllCaches();
    }

    /**
     * Удалит таблицу в бд и очистит кеши.
     */
    public function dropAllStorages(): void
    {
        $this->db->dropTable();
        $this->clearAllCaches();
    }

    /**
     * @param T $data
     */
    public function delete(BindITF $data): void
    {
        $this->db->delete($data->id);
        $this->clearRedis($data);
        $this->memo->clear();
    }

    public function clearOnCRUD(BindITF $data): void
    {

    }

    public function clearAllLists(): void
    {

    }

    public function pdo(): \PDO
    {
        return $this->db->pdo();
    }

    /**
     * @return AnyRepoMemo<T>
     */
    public function memo(): AnyRepoMemo
    {
        return $this->memo;
    }
}
//--------------------------------------------------