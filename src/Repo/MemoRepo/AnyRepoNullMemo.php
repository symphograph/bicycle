<?php

namespace Symphograph\Bicycle\Repo\MemoRepo;

use Symphograph\Bicycle\Repo\HasRepoITF;

/**
 * @template T of HasRepoITF
 */
class AnyRepoNullMemo extends AnyRepoMemo
{
    /**
     * @return class-string<T>
     */
    public static function class(): string
    {
        return '';
    }

    /**
     * @return T|null
     */
    public function byIdFromMemo(int $id): ?object
    {
        return null;
    }

    /**
     * @return T|null
     */
    public function byParamsFromMemo(array $params): ?static
    {
        return null;
    }

    public function clear(): void{}
}