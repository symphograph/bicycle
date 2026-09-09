<?php

namespace Symphograph\Bicycle\Repo\DB;

use Symphograph\Bicycle\DTO\BindITF;

/**
 * @template T of BindITF
 */
interface DBRepoITF
{

    /**
     * @return T|null
     */
    public function byId(int $id): ?object;

    /**
     * @return T[]
     */
    //public function all(): array;

    /**
     * @param T $data
     */
    //public function insert(object $data, bool $auto = false): void;

    /**
     * @param T $data
     */
    //public function replaceSafe(object $data): void;
}