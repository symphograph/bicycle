<?php

namespace Symphograph\Bicycle\Repo;

use Symphograph\Bicycle\DTO\BindITF;
use Symphograph\Bicycle\Repo\DB\AnyRepoDB;
use Symphograph\Bicycle\Repo\DB\DBRepoITF;
use Symphograph\Bicycle\Repo\Redis\SimpleRedisRepo;

/**
 * @template T of BindITF
 */
interface RepoITF extends HasClassITF
{
    /**
     * @param int $id
     * @param bool $required
     * @return T|null
     */
    public function byId(int $id, bool $required = true): ?BindITF;

    /**
     * @return T[]
     */
    public function all(): array;

    /**
     * @param T $data
     * @return void
     */
    public function update(BindITF $data): void;

    /**
     * @param T $data
     * @return void
     */
    public function delete(BindITF $data): void;

    public function clearAllRedis(): void;

    public function clearRedisById(int $id): void;

    public function clearAllStorages(): void;


    public function redis(): SimpleRedisRepo;

    /**
     * @return AnyRepoDB<T>
     */
    public function db(): DBRepoITF;
}