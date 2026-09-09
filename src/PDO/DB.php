<?php

namespace Symphograph\Bicycle\PDO;

use PDO;
use PDOStatement;
use Symphograph\Bicycle\ConnectDB;

/**
 * Фасад для работы с различными базами данных.
 * Автоматически выбирает реализацию на основе dbType в ConnectDB.
 */
class DB
{
    /**
     * Получает соответствующую реализацию DB на основе типа базы данных.
     */
    private static function getImpl(string $connectName = 'default'): BaseDB
    {
        $dbConnect = ConnectDB::byName($connectName);
        return match ($dbConnect->dbType) {
            DBType::mysql => DBMysql::getSelf($connectName),
            DBType::pgsql => PGDB::getSelf($connectName),
            default => throw new \InvalidArgumentException("Unsupported database type: {$dbConnect->dbType}")
        };
    }

    public static function lastId(string $connectName = 'default', ?string $sequence = null): ?string
    {
        return self::getImpl($connectName)::lastId($connectName, $sequence);
    }

    public static function pdo(string $connectName = 'default'): PDO
    {
        return self::getImpl($connectName)::pdo($connectName);
    }

    public static function reconnect(string $connectName = 'default'): void
    {
        self::getImpl($connectName)::reconnect($connectName);
    }

    public static function ensureConn(string $connectName = 'default'): void
    {
        self::getImpl($connectName)::ensureConn($connectName);
    }

    public static function safeTransaction(string $connectName = 'default'): void
    {
        self::getImpl($connectName)::safeTransaction($connectName);
    }

    public static function safeRollback(string $connectName = 'default'): void
    {
        self::getImpl($connectName)::safeRollback($connectName);
    }

    public static function safeCommit(string $connectName = 'default'): void
    {
        self::getImpl($connectName)::safeCommit($connectName);
    }

    public static function fetchClass(false|PDOStatement $qwe, string $class): ?object
    {
        // Для этого метода не нужна специфичная реализация, используем из BaseDB
        return BaseDB::fetchClass($qwe, $class);
    }

    public static function insertRows(string $tableName, array $rows, $connectName = 'default'): void
    {
        self::getImpl($connectName)::insertRows($tableName, $rows, $connectName);
    }

    public static function replaceRows(string $tableName, array $rows, $connectName = 'default'): void
    {
        self::getImpl($connectName)::replaceRows($tableName, $rows, $connectName);
    }

    public static function insert(string $tableName, array $params, $connectName = 'default'): void
    {
        self::getImpl($connectName)::insert($tableName, $params, $connectName);
    }

    public static function isExistsById(string $tableName, int $id, $connectName = 'default'): bool
    {
        return self::getImpl($connectName)::isExistsById($tableName, $id, $connectName);
    }

    public static function insertIgnore(string $tableName, array $params, $connectName = 'default'): void
    {
        self::getImpl($connectName)::insertIgnore($tableName, $params, $connectName);
    }

    public static function replace(string $tableName, array $params, $connectName = 'default'): void
    {
        self::getImpl($connectName)::replace($tableName, $params, $connectName);
    }

    public static function isTableExists(string $tableName): bool
    {
        // Используем реализацию из соответствующего класса
        $connectName = 'default'; // или другой способ определения подключения
        return self::getImpl($connectName)::isTableExists($tableName);
    }

    public static function qwe(string $sql, array $args = [], $connectName = 'default'): ?PDOStatement
    {
        return self::getImpl($connectName)::qwe($sql, $args, $connectName);
    }

    public static function implodeIntIn(array $ids): string
    {
        return BaseDB::implodeIntIn($ids);
    }

    public static function isUniqueCol(string $tableName, string $colName): bool
    {
        $connectName = 'default';
        return self::getImpl($connectName)::isUniqueCol($tableName, $colName);
    }

    public static function doubles(string $tableName, string $colName): void
    {
        $connectName = 'default';
        self::getImpl($connectName)::doubles($tableName, $colName);
    }

    public static function pHolders(array $args, int|string $suffix = ''): array
    {
        return BaseDB::pHolders($args, $suffix);
    }

    public static function getPDOType(mixed $value): int
    {
        return BaseDB::getPDOType($value);
    }

    /**
     * Выполняет операцию с повторными попытками при deadlock
     */
    public static function executeWithRetry(callable $operation, int $maxAttempts = 3): void
    {
        self::getImpl()::executeWithRetry($operation, $maxAttempts);
    }
}