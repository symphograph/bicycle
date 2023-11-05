<?php

namespace Symphograph\Bicycle;

use Symphograph\Bicycle\Env\Server\ServerEnv;

class ConnectDB
{
    const envPath = '/includes/env.php';
    public function __construct(
        public string $host,
        public string $name,
        public string $user,
        public string $pass,
        public string $charset = 'utf8mb4'
    )
    {
    }

    public static function byName(?string $connectName): self
    {
        $env = require dirname(ServerEnv::DOCUMENT_ROOT()) . self::envPath;
        if(empty($connectName)){
            $connectName = array_key_first($env->connects);
        }
        $con = $env->connects[$connectName];
        return new self(
            host: $con->host, name: $con->name, user: $con->user, pass: $con->pass, charset: $con->charset ?? 'utf8mb4'
        );
    }
}