<?php

namespace Symphograph\Bicycle\PDO;

use DateTime;
use JetBrains\PhpStorm\Language;
use PDO;
use PDOStatement;
use Symphograph\Bicycle\ConnectDB;
use Symphograph\Bicycle\Errors\MyErrors;
use Symphograph\Bicycle\Helpers;
use TypeError;

/**
 * Класс для работы с базой данных MySQL через PDO.
 */
class DB
{
    const options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => FALSE
    ];

    public PDO $pdo;
    private false|PDOStatement|null $stmt;

    /**
     * Конструктор класса. Устанавливает соединение с базой данных.
     *
     * @param string $charset Кодировка для соединения.
     */
    public function __construct(string $connectName = '')
    {
        $dbConnect = ConnectDB::byName($connectName);
        $dsn = "mysql:host=$dbConnect->host;dbname=$dbConnect->name;charset=$dbConnect->charset";
        $this->pdo = new PDO($dsn, $dbConnect->user, $dbConnect->pass, self::options);
    }

    /**
     * Получает экземпляр класса DB. Реализует паттерн Singleton.
     *
     * @return self Экземпляр класса DB.
     */
    public static function getSelf(): self
    {
        global $DB;
        if (!isset($DB)) {
            $DB = new self();
        }
        return $DB;
    }

    /**
     * Возвращает объект PDO для работы с базой данных.
     *
     * @return PDO Объект PDO.
     */
    public static function pdo(): PDO
    {
        return self::getSelf()->pdo;
    }

    public static function lastId()
    {
        return self::pdo()->lastInsertId();
    }

    /**
     * Выполняет SQL-запрос и возвращает объект PDOStatement.
     *
     * @param string $sql  SQL-запрос.
     * @param array  $args Параметры запроса (по умолчанию пустой массив).
     *
     * @return false|PDOStatement Объект PDOStatement с результатами запроса.
     */
    public static function qwe(#[Language('SQL')] string $sql, array $args = []): false|PDOStatement
    {
        $DB = self::getSelf();
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
     *
     * @param string $sql SQL-запрос.
     *
     * @return false|PDOStatement Объект PDOStatement с результатами запроса.
     */
    private function query(#[Language('SQL')] string $sql): false|PDOStatement
    {
        return $this->pdo->query($sql);
    }

    /**
     * Выполняет SQL-запрос с параметрами и возвращает объект PDOStatement.
     *
     * @param string $sql  SQL-запрос.
     * @param array  $args Параметры запроса.
     *
     * @return false|PDOStatement Объект PDOStatement с результатами запроса.
     */
    private function execute(string $sql, array $args): false|PDOStatement
    {
        $this->stmt = $this->pdo->prepare($sql);
        $this->bindValues($args);
        $this->stmt->execute();
        return $this->stmt;
    }

    /**
     * Генерирует placeholders для использования в SQL-запросе.
     *
     * @param array       $row    Ассоциативный массив с данными.
     * @param int|string  $suffix Суффикс для placeholders.
     *
     * @return string Строка с placeholders.
     */
    private static function rowInsertPHolders(array $row, int|string $suffix = ''): string
    {
        $pHolders = self::pHolders($row, $suffix);
        return '(' . implode(', ', $pHolders) . ')';
    }

    /**
     * Генерирует строку с именами полей для использования в SQL-запросе.
     *
     * @param array $props Ассоциативный массив с данными.
     *
     * @return string Строка с именами полей.
     */
    private static function propsSting(array $props): string
    {
        $propNames = array_keys($props);
        return '(' . implode(', ', $propNames) . ')';
    }

    /**
     * Вставляет несколько строк в таблицу.
     *
     * @param string $tableName Имя таблицы.
     * @param array  $rows      Массив с данными для вставки.
     *
     * @throws MyErrors В случае ошибки.
     */
    public static function insertRows(string $tableName, array $rows): void
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

        $DB = self::getSelf();
        $DB->stmt = $DB->pdo->prepare($sql);
        foreach ($rows as $suffix => $args) {
            $DB->bindValues($args, $suffix + 1);
        }
        //$DB->pdo->beginTransaction();
        $DB->stmt->execute();
        //$DB->pdo->commit();
    }

    /**
     * Вставляет одну строку в таблицу или обновляет существующую, если запись уже существует.
     *
     * @param string $tableName Имя таблицы.
     * @param array  $params    Ассоциативный массив с данными для вставки/обновления.
     */
    public static function replace(string $tableName, array $params): void
    {
        $propsSting = self::propsSting($params);
        $valuesString = self::rowInsertPHolders($params);
        $paramsForUpdateStr = self::rowUpdatePHolders($params);
        $params = self::castingTypes($params);
        $params = self::addParamsWithSuffixUpd($params);
        $sql = "INSERT INTO $tableName $propsSting VALUES $valuesString on duplicate key update $paramsForUpdateStr";

        $DB = self::getSelf();
        $DB->stmt = $DB->pdo->prepare($sql);
        $DB->bindValues($params);
        $DB->stmt->execute();
    }

    /**
     * Добавляет в массив параметры с суффиксом '_upd' у ключа.
     *
     * @param array $params Массив параметров.
     *
     * @return array Массив с добавленными параметрами.
     */
    private static function addParamsWithSuffixUpd(array $params): array
    {
        $newParams = [];
        foreach ($params as $key => $value) {
            $newParams[$key . '_upd'] = $value;
        }
        return array_merge($params, $newParams);
    }

    private static function castingTypes(array $props): array
    {
        $params = [];
        foreach ($props as $propName => $value){
            $params[$propName] = self::castValueType($value);
        }
        return $params;
    }

    private static function castValueType(mixed $value): mixed
    {
        return match (true) {
            is_array($value),
            is_object($value) => json_encode($value),
            is_bool($value) => intval($value),
            default => $value
        };
    }

    /**
     * Привязывает значения к параметрам SQL-запроса.
     *
     * @param array       $args   Ассоциативный массив с данными для привязки.
     * @param int|string  $suffix Суффикс для имен параметров.
     */
    private function bindValues(array $args, int|string $suffix = ''): void
    {
        foreach ($args as $propName => $value) {
            $pdoType = self::getPDOType($value);
            $this->stmt->bindValue(":$propName$suffix", $value, $pdoType);
        }
    }

    /**
     * Генерирует placeholders для использования в SQL-запросе.
     *
     * @param array       $args   Ассоциативный массив с данными.
     * @param int|string  $suffix Суффикс для placeholders.
     *
     * @return array Массив с placeholders.
     */
    public static function pHolders(array $args, int|string $suffix = ''): array
    {
        $propNames = array_keys($args);
        return array_map(fn($propName) => ":$propName$suffix", $propNames);
    }

    /**
     * Генерирует строку для использования в SQL-запросе при обновлении записи.
     *
     * @param array $args Ассоциативный массив с данными.
     *
     * @return string Строка с placeholders для обновления.
     */
    private static function rowUpdatePHolders(array $args): string
    {
        $parNames = array_keys($args);
        $paramsForUpdate = [];
        foreach ($parNames as $name) {
            $paramsForUpdate[] = $name . '=:' . $name . '_upd';
        }
        return implode(', ', $paramsForUpdate);
    }

    /**
     * Возвращает PDO-тип данных на основе переданного значения.
     *
     * @param mixed $value Значение для определения типа.
     *
     * @return int Константа PDO::PARAM_* для типа данных.
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
            default => throw new \TypeError('invalid type for DB')
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
        return !!DB::qwe($sql, ['tableName' => $tableName])->rowCount();
    }

    public static function implodeIntIn(array $ids): string
    {
        Helpers::isArrayIntList($ids)
        or throw new TypeError('array is not ints');
        return '(' . implode(',', $ids) . ')';
    }
}