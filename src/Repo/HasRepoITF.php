<?php

namespace Symphograph\Bicycle\Repo;

use Symphograph\Bicycle\DTO\BindITF;

/**@template T of HasRepoITF */
interface HasRepoITF extends BindITF
{
    /** @return RepoITF<T> */
    static function repo(): RepoITF;
}