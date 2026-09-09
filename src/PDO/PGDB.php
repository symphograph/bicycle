<?php

namespace Symphograph\Bicycle\PDO;

use PDO;
use Symphograph\Bicycle\ConnectDB;
use Symphograph\Bicycle\Errors\MyErrors;

/**
 * Класс для работы с базой данных PostgreSQL через PDO.
 */
class PGDB extends BaseDB
{
    protected static array $instances = [];

    public function __construct(string $connectName = 'default') {
        $dbConnect = ConnectDB::byName($connectName);
        $params = [
            'host' => $dbConnect->host,
            'port' => $dbConnect->port,
            'dbname' => $dbConnect->name
        ];

        if (!empty($dbConnect->charset)) {
            $params['options'] = "--client_encoding={$dbConnect->charset}";
        }

        $dsn = $this->buildDSN($params);
        $this->pdo = new PDO($dsn, $dbConnect->user, $dbConnect->pass, self::options);
    }

    protected function getDriver(): string {
        return 'pgsql';
    }

    public static function getSelf($connectName = 'default'): self
    {
        if (isset(self::$instances[$connectName])) {
            return self::$instances[$connectName];
        }

        if ($connectName === 'default') {
            $envConnectName = ConnectDB::getDefaultConnectName();
        } else {
            $envConnectName = $connectName;
        }
        self::$instances[$connectName] = new self($envConnectName);
        return self::$instances[$connectName];
    }

    /**
     * Вставляет или обновляет несколько строк в таблице (PostgreSQL UPSERT).
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

        // PostgreSQL использует ON CONFLICT вместо ON DUPLICATE KEY
        $sql = "INSERT INTO $tableName $propsString VALUES $valuesString 
                ON CONFLICT (id) DO UPDATE SET $updateString";

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
        $updateParts = [];
        foreach (array_keys($firstRow) as $column) {
            if ($column !== 'id') {
                $updateParts[] = "$column = EXCLUDED.$column";
            }
        }
        return implode(', ', $updateParts);
    }

    /**
     * Вставляет одну строку в таблицу или обновляет существующую (PostgreSQL UPSERT).
     */
    public static function replace(string $tableName, array $params, $connectName = 'default'): void
    {
        $propsSting = self::propsSting($params);
        $valuesString = self::rowInsertPHolders($params);
        $paramsForUpdateStr = self::rowUpdatePHoldersPG($params);
        $params = self::castingTypes($params);

        $sql = "INSERT INTO $tableName $propsSting VALUES $valuesString 
                ON CONFLICT (id) DO UPDATE SET $paramsForUpdateStr";

        $DB = self::getSelf($connectName);
        $DB->stmt = $DB->pdo->prepare($sql);
        $DB->bindValues($params);
        $DB->stmt->execute();
    }

    /**
     * Генерирует строку для UPDATE в PostgreSQL (использует EXCLUDED).
     */
    private static function rowUpdatePHoldersPG(array $args): string
    {
        $parNames = array_keys($args);
        $paramsForUpdate = [];
        foreach ($parNames as $name) {
            if ($name !== 'id') {
                $paramsForUpdate[] = $name . ' = EXCLUDED.' . $name;
            }
        }
        return implode(', ', $paramsForUpdate);
    }

    public static function isTableExists(string $tableName): bool
    {
        $sql = "
            SELECT 
              table_name
            FROM    
              information_schema.tables    
            WHERE    
              table_schema = 'public' AND    
              table_name = :tableName
        ";
        return !!self::qwe($sql, ['tableName' => $tableName])->rowCount();
    }


}