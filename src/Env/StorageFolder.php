<?php

namespace Symphograph\Bicycle\Env;

class StorageFolder
{
    public function __construct(
        public string $data,
        public string $public,
        public string $tmp
    ){}
}