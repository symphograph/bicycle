<?php

namespace Symphograph\Bicycle\Env;

class EmailSecret
{
    public function __construct(
        public string $email,
        public string $password
    ){}
}