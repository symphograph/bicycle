<?php

namespace Symphograph\Bicycle;

use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\PDO\DBType;

class ConnectDB
{
    public DBType $dbType;
    public string $charset;

    public function __construct(
        public string $host,
        public int $port,
        public string $name,
        public string $user,
        public string $pass,
        string $dbType,
        ?string $charset = null,
    )
    {
        $this->dbType = DBType::from($dbType);
        $this->charset = $charset ?? 'utf8mb4';
    }

    public static function byName(?string $connectName): self
    {
        $connects = Env::dbConnects();
        if(empty($connectName) || $connectName === 'default'){
            $connectName = array_key_first($connects);
        }
        return $connects[$connectName];
    }

    public static function getDefaultConnectName(): string
    {
        $connects = Env::dbConnects();
        return array_key_first($connects);
    }
}