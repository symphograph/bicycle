<?php

namespace Symphograph\Bicycle\Env\Server;

use Symphograph\Bicycle\DTO\BindTrait;

/**
 * Класс, представляющий окружение сервера для HTTP-запросов.
 *
 * @package Symphograph\Bicycle\Env\Server
 */
class ServerEnvHttp extends ServerEnv
{
    use BindTrait;
    public function __construct()
    {
        $this->bindSelf($_SERVER);
    }
}