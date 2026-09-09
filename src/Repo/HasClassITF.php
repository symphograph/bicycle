<?php

namespace Symphograph\Bicycle\Repo;

use Symphograph\Bicycle\DTO\BindITF;

/**
 * @template T of BindITF
 */
interface HasClassITF {
    /** @return class-string<T> */
    public static function class(): string;
}