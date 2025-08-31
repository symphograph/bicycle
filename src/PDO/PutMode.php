<?php

namespace Symphograph\Bicycle\PDO;

enum PutMode: string
{
    case safeReplace = 'safeReplace';
    case insert = 'insert';
    case insertRows = 'insertRows';
    case replaceRows = 'replaceRows';
    case insertAuto  = 'insertAuto';

    public function execute(string $tableName, array $params): void
    {
        match ($this) {
            self::safeReplace => DB::replace($tableName, $params),
            self::replaceRows=>DB::replaceRows($tableName, $params),
            self::insert, self::insertAuto => DB::insert($tableName, $params),
            self::insertRows => DB::insertRows($tableName, $params),
        };
    }
}
