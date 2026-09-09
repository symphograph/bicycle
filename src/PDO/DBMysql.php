<?php

namespace Symphograph\Bicycle\PDO;

use PDO;
use PDOException;
use Symphograph\Bicycle\ConnectDB;
use Symphograph\Bicycle\Errors\MyErrors;

/**
 * Класс для работы с базой данных MySQL через PDO.
 */
class DBMysql extends BaseDB
{
    protected static array $instances = [];

    public function __construct(string $connectName = 'default') {
        $dbConnect = ConnectDB::byName($connectName);
        $dsn = $this->buildDSN([
            'host' => $dbConnect->host,
            'port' => $dbConnect->port, // Добавляем порт
            'dbname' => $dbConnect->name,
            'charset' => $dbConnect->charset
        ]);
        $this->pdo = new PDO($dsn, $dbConnect->user, $dbConnect->pass, self::options);
    }

    protected function getDriver(): string {
        return 'mysql';
    }

    public static function getSelf(string $connectName = 'default'): self
    {
        if (!isset(self::$instances[$connectName])) {
            if ($connectName === 'default') {
                $envConnectName = ConnectDB::getDefaultConnectName();
            } else {
                $envConnectName = $connectName;
            }
            self::$instances[$connectName] = new self($envConnectName);
        }

        return self::$instances[$connectName];
    }

    public static function reconnect(string $connectName = 'default'): void
    {
        if (isset(self::$instances[$connectName])) {
            unset(self::$instances[$connectName]);
        }
        // Новое соединение будет создано при следующем вызове getSelf()
    }

    /**
     * Проверяет соединение и переподключается при необходимости
     */
    public static function ensureConn(string $connectName = 'default'): void
    {
        try {
            self::tryQuery($connectName);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'MySQL server has gone away')) {
                self::reconnect($connectName);
                self::tryQuery($connectName);
            } else {
                throw $e;
            }
        }
    }

    private static  function tryQuery(string $connectName = 'default'): void
    {
       self::getSelf($connectName)->pdo->query('SELECT 1')->fetchColumn();
    }

    /**
     * Вставляет или обновляет несколько строк в таблице.
     */
    public static function replaceRows(string $tableName, array $rows, $connectName = 'default'): void
    {
        if (!array_is_list($rows) || empty($rows)) {
            throw new MyErrors('rows must be a list and not empty');
        }

        $propsString = self::propsSting($rows[0]);
        $rowStrings = [];
        $castedRows = [];
        foreach ($rows as $suffix => $row) {
            $castedRow = self::castingTypes($row);
            $castedRows[] = $castedRow;
            $rowStrings[] = self::rowInsertPHolders($castedRow, $suffix + 1);
        }

        $valuesString = implode(', ', $rowStrings);
        $updateString = self::rowUpdatePHoldersForMultipleRows($rows[0]);

        $sql = "INSERT INTO $tableName $propsString VALUES $valuesString ON DUPLICATE KEY UPDATE $updateString";

        $DB = self::getSelf($connectName);
        $DB->stmt = $DB->pdo->prepare($sql);
        foreach ($castedRows as $suffix => $args) {
            $DB->bindValues($args, $suffix + 1);
        }

        $DB->stmt->execute();
    }

    /**
     * Генерирует строку для обновления в SQL-запросе для множественной вставки.
     */
    private static function rowUpdatePHoldersForMultipleRows(array $firstRow): string
    {
        $updateParts = array_map(function ($column) {
            return "$column=VALUES($column)";
        }, array_keys($firstRow));

        return implode(', ', $updateParts);
    }

    /**
     * Вставляет одну строку в таблицу или обновляет существующую.
     */
    public static function replace(string $tableName, array $params, $connectName = 'default'): void
    {
        $propsSting = self::propsSting($params);
        $valuesString = self::rowInsertPHolders($params);
        $paramsForUpdateStr = self::rowUpdatePHolders($params);
        $params = self::castingTypes($params);
        $params = self::addParamsWithSuffixUpd($params);
        $sql = "INSERT INTO $tableName $propsSting VALUES $valuesString 
                on duplicate key update $paramsForUpdateStr";

        $DB = self::getSelf($connectName);
        $DB->stmt = $DB->pdo->prepare($sql);
        $DB->bindValues($params);
        $DB->stmt->execute();
    }

    /**
     * Генерирует строку для использования в SQL-запросе при обновлении записи.
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
     * Добавляет в массив параметры с суффиксом '_upd' у ключа.
     */
    private static function addParamsWithSuffixUpd(array $params): array
    {
        $newParams = [];
        foreach ($params as $key => $value) {
            $newParams[$key . '_upd'] = $value;
        }
        return array_merge($params, $newParams);
    }

    protected static function castValueType(mixed $value): mixed
    {
        return match (true) {
            is_array($value),
            is_object($value) => json_encode($value),
            is_bool($value) => intval($value), // MySQL не поддерживает boolean напрямую
            default => $value
        };
    }


}