<?php

namespace Symphograph\Bicycle;

class ConnectDB
{
    public function __construct(
        public string $host,
        public string $name,
        public string $user,
        public string $pass,
    )
    {
    }

    public static function byName(?string $connectName): self
    {
        $env = require dirname($_SERVER['DOCUMENT_ROOT']) . '/includes/env.php';
        if(empty($connectName)){
            $connectName = array_key_first($env->connects);
        }
        $con = $env->connects->$connectName;
        return new self(
            host: $con->host, name: $con->name, user: $con->user, pass: $con->pass
        );
    }
}