<?php

namespace Symphograph\Bicycle\PDO;

enum PutMode: string
{
    case safeReplace = 'safeReplace';
    case insert = 'insert';
    case insertRows = 'insertRows';
    case replaceRows = 'replaceRows';

    public function execute(string $tableName, array $params): void
    {
        match ($this) {
            PutMode::safeReplace => DB::replace($tableName, $params),
            PutMode::replaceRows=>DB::replaceRows($tableName, $params),
            PutMode::insert => DB::insert($tableName, $params),
            PutMode::insertRows => DB::insertRows($tableName, $params),
        };
    }
}
