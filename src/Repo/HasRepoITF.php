<?php

namespace Symphograph\Bicycle\Repo;

use Symphograph\Bicycle\DTO\BindITF;

interface HasRepoITF extends BindITF
{
    /** @return RepoITF<static> */
    static function repo(): RepoITF;
}