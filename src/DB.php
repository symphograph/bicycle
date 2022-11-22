<?php
namespace Symphograph\Bicycle;

use PDO;
use PDOException;
use PDOStatement;

class DB
{
    public PDO|null $pdo;
    private array   $opt;
    public string|null $pHolders;
    public array|null $parArr;

    public function __construct(
        string $connectName = '',
        string $charset = 'utf8mb4',
        bool $flat = false,
        object $env = null
    )
    {
        if($flat) return;

        if (empty($connectName)) {
            $connectName = 0;
        }
        $con = (object)$env->connects[$_SERVER['SERVER_NAME']][$connectName];

        $dsn = "mysql:host=$con->Host;dbname=$con->Name;charset=$charset";
        $this->opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => FALSE
        ];

        try {
            $this->pdo = new PDO($dsn, $con->User, $con->Pass, $this->opt);
        } catch (PDOException $ex) {
            die('dbError');
        }

    }

    public function qwe($sql, $args = NULL): bool|PDOStatement
    {
        if (!$args) {
            return self::query($sql);
        }
        return self::execute($sql, $args);
    }

    private function execute(string $sql, array $args): bool|PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($args);

        } catch (PDOException $ex) {

            $log_text = self::prepLog($ex->getTraceAsString(), $sql, $ex->getMessage());
            self::writelog('sql_error', $log_text);
            return false;
        }
        return $stmt ?? false;
    }

    private function query($sql): bool|PDOStatement
    {
        try {
            $result = $this->pdo->query($sql);

        } catch (PDOException $ex) {

            $log_text = self::prepLog($ex->getTraceAsString(), $sql, $ex->getMessage());
            self::writelog('sql_error', $log_text);
            return false;

        }
        return $result ?? false;
    }

    public static function replace(string $tableName, array $params): bool
    {
        global $DB;
        self::connect();

        $rd = self::replaceData($tableName,$params);
        return $DB->qwe($rd->sql,$rd->params);
    }

    private static function connect(): void
    {
        global $DB, $env;
        if(!isset($DB)){
            $DB = new DB(env: $env);
        }
    }

    public static function pHolders(array $list): string
    {
        //return rtrim(str_repeat('?, ', count($list)), ', ') ;

        $inKeys = array_map(function ($key) {
            return ':var_' . intval($key);
        }, array_keys($list));
        return implode(', ', $inKeys);
    }

    public static function pHoldsArr(array $list): array
    {
        $arr = [];
        foreach ($list as $key => $val) {
            $arr['var_' . intval($key)] = $val;
        }
        return $arr;
    }

    private function prepLog(string $trace, string $sql, string $error): string
    {
        return date("Y-m-d H:i:s") . "\t" . $error . "\t" . $trace . "\r\n" . $sql . "\r\n";
    }

    private function writelog($typelog, $log_text): void
    {
        $log = fopen(dirname($_SERVER['DOCUMENT_ROOT']) . '/logs/' . $typelog . '.txt', 'a+');
        fwrite($log, "$log_text\r\n");
        fclose($log);
    }

    public function __destruct()
    {
        $pdo = null;
    }

    public static function prepMul(array $params): DB
    {
        $parNames = array_keys($params);
        $phArr = [];
        foreach ($params as $parName => $parms){
            $i = 0;
            $phArr = [];
            foreach ($parms as $p){
                $phArr[] = self::paramNamer($parNames,$i);
                $i++;
            }
        }
        $pHolders = implode(', ',$phArr);


        $parArr = [];
        foreach ($params[$parNames[0]] as $k => $v){
            foreach ($parNames as $name){
                $parArr[$name.'_'.$k] = $params[$name][$k];
            }
        }

        $result = new self(flat: true);
        $result->pHolders = $pHolders;
        $result->parArr = $parArr;
        return $result;
    }

    private static function paramNamer(array $parNames, $rowNum): string
    {
        $arr = [];
        foreach ($parNames as $name) {
            $arr[] = ':' . $name . '_' . $rowNum;
        }
        return '(' . implode(', ', $arr) . ')';
    }

    public static function replaceData(string $tableName, array $params): object
    {
        return (object) [
            'sql' => self::getReplaceByUpdateQueryStr($tableName, $params),
            'params' => self::phParamsForUpd($params)
        ];
    }

    private static function getReplaceByUpdateQueryStr(string $tableName, array $params): string
    {

        $parNamesStr = self::colNamesStr($params);
        $phNamesStr = self::valuesPhNamesStr($params);
        $paramsForUpdateStr = self::paramsForUpdateStr($params);

        return "
            insert into $tableName 
            $parNamesStr
                VALUES 
            $phNamesStr
            on duplicate key update 
                 $paramsForUpdateStr";
    }

    private static function colNamesStr(array $params): string
    {
        $parNames = array_keys($params);
        return ' (' . implode(',',$parNames) . ') ';
    }

    private static function valuesPhNamesStr(array $params): string
    {
        $parNames = array_keys($params);
        $phNames = [];
        foreach ($parNames as $name){
            $phNames[] = ':' . $name;
        }
        return ' (' . implode(',', $phNames) . ') ';
    }

    private static function phParamsForUpd(array $params): array
    {
        $arr = [];
        foreach ($params as $k => $v)
        {
            $arr[$k . '_upd'] = $v;
        }
        return array_merge($params, $arr);
    }

    private static function paramsForUpdateStr(array $params): string
    {
        $parNames = array_keys($params);
        $paramsForUpdate = [];
        foreach ($parNames as $name){
            $paramsForUpdate[] = $name . '=:' . $name . '_upd';
        }
        return implode(',',$paramsForUpdate);
    }
}