<?php

namespace Symphograph\Bicycle\Env\Server;

use Symphograph\Bicycle\DTO\BindTrait;

class ServerEnvHttp extends ServerEnv
{
    use BindTrait;
    public function __construct()
    {
        $this->bindSelf($_SERVER);
    }
}