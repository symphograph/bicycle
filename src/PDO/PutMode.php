<?php

namespace Symphograph\Bicycle\PDO;

enum PutMode: string
{
    case safeReplace = 'safeReplace';
    case insert = 'insert';

    public function execute(string $tableName, array $params): void
    {
        match ($this) {
            PutMode::safeReplace => DB::replace($tableName, $params),
            PutMode::insert => DB::insert($tableName, $params),
            default => null
        };
    }
}
