<?php

namespace Symphograph\Bicycle\PDO;

use DateTime;
use JetBrains\PhpStorm\Language;
use PDO;
use PDOStatement;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Errors\MyErrors;
use Symphograph\Bicycle\Helpers\Arr;
use TypeError;

/**
 * Абстрактный базовый класс для работы с базами данных через PDO.
 */
abstract class BaseDB
{
    const array options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ];

    protected static array $instances = [];

    public PDO $pdo;
    protected false|PDOStatement|null $stmt;

    abstract public function __construct(string $connectName = 'default');

    protected function buildDSN(array $params): string {
        $dsnParts = [];
        foreach ($params as $key => $value) {
            if (!empty($value)) {
                $dsnParts[] = "{$key}={$value}";
            }
        }
        return $this->getDriver() . ':' . implode(';', $dsnParts);
    }

    abstract protected function getDriver(): string;

    public static function lastId(string $connectName = 'default', ?string $sequence = null): ?string
    {
        $pdo = self::pdo($connectName);
        if ($sequence) {
            return $pdo->lastInsertId($sequence) ?: null;
        }
        return $pdo->lastInsertId() ?: null;
    }

    public static function pdo(string $connectName = 'default'): PDO
    {
        return static::getSelf($connectName)->pdo;
    }

    public static function safeTransaction(string $connectName = 'default'): void
    {
        if(self::pdo($connectName)->inTransaction()) return;
        self::pdo($connectName)->beginTransaction()
            ?: throw new AppErr('Transaction failed');
    }

    public static function safeRollback(string $connectName = 'default'): void
    {
        if(!self::pdo($connectName)->inTransaction()) return;
        self::pdo($connectName)->rollBack();
    }

    public static function safeCommit(string $connectName = 'default'): void
    {
        if(!self::pdo($connectName)->inTransaction()) return;
        self::pdo($connectName)->commit();
    }

    abstract public static function getSelf(string $connectName = 'default'): self;

    abstract public static function reconnect(string $connectName = 'default'): void;

    abstract public static function ensureConn(string $connectName = 'default'): void;

    public static function fetchClass(false|PDOStatement $qwe, string $class): ?object
    {
        if (!$qwe) {
            return null;
        }
        return $qwe->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class)[0] ?? null;
    }

    /**
     * Вставляет несколько строк в таблицу.
     */
    public static function insertRows(string $tableName, array $rows, $connectName = 'default'): void
    {
        if (!array_is_list($rows)) {
            throw new MyErrors('rows must be a list');
        }
        $propsSting = self::propsSting($rows[0]);

        $rowStrings = [];
        foreach ($rows as $suffix => $row) {
            $rowStrings[] = self::rowInsertPHolders($row, $suffix + 1);
        }

        $valuesString = implode(', ', $rowStrings);

        $sql = "INSERT INTO $tableName $propsSting VALUES $valuesString";

        $DB = static::getSelf($connectName);
        $DB->stmt = $DB->pdo->prepare($sql);
        foreach ($rows as $suffix => $args) {
            $DB->bindValues($args, $suffix + 1);
        }

        $DB->stmt->execute();
    }

    public static function insert(string $tableName, array $params, $connectName = 'default'): void
    {
        $propsSting = self::propsSting($params);
        $valuesString = self::rowInsertPHolders($params);
        $params = self::castingTypes($params);
        $sql = "INSERT INTO $tableName $propsSting VALUES $valuesString";

        $DB = static::getSelf($connectName);
        $DB->stmt = $DB->pdo->prepare($sql);
        $DB->bindValues($params);
        $DB->stmt->execute();
    }

    public static function isExistsById(string $tableName, int $id, $connectName = 'default'): bool
    {
        $sql = "SELECT id FROM $tableName WHERE id = :id";
        $params = [':id' => $id];
        return self::qwe($sql, $params, $connectName)->rowCount() > 0;
    }

    /**
     * Вставляет запись, если её нет.
     */
    public static function insertIgnore(string $tableName, array $params, $connectName = 'default'): void
    {
        $exists = isset($params['id']) && self::isExistsById($tableName, $params['id'], $connectName);
        if ($exists) return;

        self::insert($tableName, $params, $connectName);
    }

    /**
     * Генерирует строку с именами полей для использования в SQL-запросе.
     */
    protected static function propsSting(array $props): string
    {
        $propNames = array_keys($props);
        return '(' . implode(', ', $propNames) . ')';
    }

    /**
     * Генерирует placeholders для использования в SQL-запросе.
     */
    protected static function rowInsertPHolders(array $row, int|string $suffix = ''): string
    {
        $pHolders = self::pHolders($row, $suffix);
        return '(' . implode(', ', $pHolders) . ')';
    }

    /**
     * Генерирует placeholders для использования в SQL-запросе.
     */
    public static function pHolders(array $args, int|string $suffix = ''): array
    {
        $propNames = array_keys($args);
        return array_map(fn($propName) => ":$propName$suffix", $propNames);
    }

    /**
     * Привязывает значения к параметрам SQL-запроса.
     */
    protected function bindValues(array $args, int|string $suffix = ''): void
    {
        foreach ($args as $propName => $value) {
            $pdoType = self::getPDOType($value);
            $this->stmt->bindValue(":$propName$suffix", $value, $pdoType);
        }
    }

    /**
     * Возвращает PDO-тип данных на основе переданного значения.
     */
    public static function getPDOType(mixed $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_string($value),
            is_float($value),
            is_double($value),
            ($value instanceof DateTime),
            is_array($value) => PDO::PARAM_STR,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            is_resource($value) => PDO::PARAM_LOB,
            default => throw new TypeError('invalid type for DB')
        };
    }

    /**
     * Выполняет SQL-запрос с параметрами и возвращает объект PDOStatement.
     */
    protected function execute(string $sql, array $args): ?PDOStatement
    {
        $this->stmt = $this->pdo->prepare($sql);
        $this->bindValues($args);
        $this->stmt->execute();
        return $this->stmt;
    }

    protected static function castingTypes(array $props): array
    {
        return array_map(function ($value) {
            return self::castValueType($value);
        }, $props);
    }

    protected static function castValueType(mixed $value): mixed
    {
        return match (true) {
            is_array($value),
            is_object($value) => json_encode($value),
            is_bool($value) => $value, // Для баз, поддерживающих boolean
            default => $value
        };
    }

    public static function isTableExists(string $tableName): bool
    {
        $sql = "
            SELECT 
              TABLE_NAME
            FROM    
              INFORMATION_SCHEMA.TABLES    
            WHERE    
              table_schema = DATABASE() AND    
              table_name LIKE :tableName
        ";
        return !!self::qwe($sql, ['tableName' => $tableName])->rowCount();
    }

    /**
     * Выполняет SQL-запрос и возвращает объект PDOStatement.
     */
    public static function qwe(
        #[Language('SQL')] string $sql,
        array                     $args = [],
                                  $connectName = 'default'
    ): ?PDOStatement
    {
        $DB = static::getSelf($connectName);
        if (empty($args)) {
            return $DB->query($sql);
        }

        $newSql = $sql;
        $newArgs = [];

        foreach ($args as $argName => $argValue) {
            if (!is_array($argValue)) {
                $newArgs[$argName] = $argValue;
                continue;
            }

            // Генерируем уникальные ключи для его элементов
            $keys = array_map(fn($index) => "$argName$index", range(0, count($argValue) - 1));

            // Заменяем плейсхолдер в SQL на сгенерированные ключи
            $newSql = str_replace(":$argName", ':' . implode(', :', $keys), $newSql);

            // Добавляем новые ключи и их значения в новый массив аргументов
            $newArgs += array_combine($keys, $argValue);
        }

        return $DB->execute($newSql, $newArgs);
    }

    /**
     * Выполняет SQL-запрос без параметров и возвращает объект PDOStatement.
     */
    protected function query(#[Language('SQL')] string $sql): ?PDOStatement
    {
        return $this->pdo->query($sql) ?: null;
    }

    public static function implodeIntIn(array $ids): string
    {
        Arr::isArrayIntList($ids)
        or throw new TypeError('array is not ints');
        return '(' . implode(',', $ids) . ')';
    }

    public static function isUniqueCol(string $tableName, string $colName): bool
    {
        $sql = "
            SELECT COUNT($colName) = COUNT(DISTINCT $colName) AS is_unique
            FROM $tableName";
        $qwe = self::qwe($sql)->fetchColumn();
        var_dump($qwe);
        return false;
    }

    public static function doubles(string $tableName, string $colName): void
    {
        $sql = "SELECT $colName, COUNT(*) as count
                FROM $tableName
                WHERE $colName IS NOT NULL AND $colName != ''
                GROUP BY $colName
                HAVING COUNT(*) > 1";
    }

    /**
     * Выполняет операцию с повторными попытками при deadlock
     */
    public static function executeWithRetry(callable $operation, int $maxAttempts = 3): void
    {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                $operation();
                return;

            } catch (\PDOException $e) {
                $attempt++;

                if (self::isDeadlock($e) && $attempt < $maxAttempts) {
                    usleep(100000 * $attempt); // 100ms, 200ms, 300ms
                    continue;
                }

                throw $e;
            }
        }
    }

    /**
     * Проверяет, является ли исключение deadlock
     */
    private static function isDeadlock(\PDOException $e): bool
    {
        return $e->getCode() == 40001 && str_contains($e->getMessage(), 'Deadlock');
    }

    // Абстрактные методы для специфичной реализации в дочерних классах
    abstract public static function replace(string $tableName, array $params, $connectName = 'default'): void;
    abstract public static function replaceRows(string $tableName, array $rows, $connectName = 'default'): void;
}