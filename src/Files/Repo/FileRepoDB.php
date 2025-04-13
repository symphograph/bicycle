<?php

namespace Symphograph\Bicycle\Files\Repo;

use Symphograph\Bicycle\Errors\Files\FileNotExistsInDBErr;
use Symphograph\Bicycle\Files\FileDTO;
use Symphograph\Bicycle\PDO\DB;

class FileRepoDB
{
    public static function byHash(string $hash, bool $required = true): ?FileDTO
    {
        $sql = "select * from Files where hash = :hash";
        $params = ['hash' => $hash];
        $result = DB::qwe($sql, $params)?->fetchObject(FileDTO::class);
        if($required && empty($result)) throw new FileNotExistsInDBErr($hash);
        return $result ?: null;
    }

    public static function byId(int $id, bool $required = true): ?FileDTO
    {
        $sql = "select * from Files where id = :id";
        $params = ['id' => $id];
        $result = DB::qwe($sql, $params)?->fetchObject(FileDTO::class);
        if($required && empty($result)) throw new FileNotExistsInDBErr($id);
        return $result ?: null;
    }
}