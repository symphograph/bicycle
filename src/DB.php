<?php
namespace Symphograph\Bicycle;


use PDO;
use PDOStatement;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use TypeError;


class DB
{
    public ?PDO $pdo;
    private ?array    $opt;
    public ?string $pHolders;
    public ?array  $parArr;
    private ConnectDB $connect;

    public function __construct(
        string $connectName = '',
        string $charset = 'utf8mb4',
        bool $flat = false
    )
    {
        if($flat) return;

        $con = $this->connect = ConnectDB::byName($connectName);

        $dsn = "mysql:host=$con->host;dbname=$con->name;charset=$charset";
        $this->opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => FALSE
        ];
        $this->pdo = new PDO($dsn, $con->user, $con->pass, $this->opt);
    }

    public function qwe(string $sql, array $args = NULL): bool|PDOStatement
    {
        if (!$args) {
            return self::query($sql);
        }
        $args = self::mergeArgs($args);
        return self::execute($sql, $args);
    }

    private static function mergeArgs(array $args): array
    {
        $arr1 = [];
        $arr2 = [];
        foreach ($args as $k1 => $element){
            if(!is_array($element)){
                $arr1[$k1] = $element;
                continue;
            }
            foreach ($element as $k2 => $el){
                $arr2[$k2] = $el;
            }
        }
        return array_merge($arr1, $arr2);
    }

    private function execute(string $sql, array $args): PDOStatement
    {
        //printr($args);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }

    private function query($sql): PDOStatement
    {
        return $this->pdo->query($sql);
    }

    public static function replace(string $tableName, array $params): bool
    {
        global $DB;
        self::connect();

        $rd = self::replaceData($tableName,$params);
        return boolval($DB->qwe($rd->sql,$rd->params));
    }

    public static function connect(): void
    {
        global $DB;
        if(!isset($DB)){
            $DB = new DB();
        }
    }

    public static function pdo(): PDO
    {
        global $DB;
        self::connect();
        return $DB->pdo;
    }

    public static function lastId(): int
    {
        return self::pdo()->lastInsertId();
    }

    public static function pHolders(array $list, int $i = 1): string
    {
        $inKeys = array_map(
            fn($key) =>
                ':var'.$i.'_' . intval($key),
            array_keys($list)
        );
        return implode(', ', $inKeys);
    }

    public static function pHoldsArr(array $list, int $i = 1): array
    {
        $arr = [];
        foreach ($list as $key => $val) {
            $arr['var'.$i.'_' . intval($key)] = $val;
        }
        return $arr;
    }

    private function prepLog(string $trace, string $sql, string $error): string
    {
        return date("Y-m-d H:i:s") . "\t" . $error . "\t" . $trace . "\r\n" . $sql . "\r\n";
    }

    private function writeLog($logText): void
    {
        $file = self::getLogFilename();
        if(!file_exists($file)){
            FileHelper::fileForceContents($file, '');
        }
        $log = fopen($file, 'a+');
        fwrite($log, "$logText\r\n");
        fclose($log);
    }

    private static function getLogFilename(): string
    {
        return dirname(ServerEnv::DOCUMENT_ROOT()). '/logs/sqlErrors/' . date('Y-m-d') . '.log';
    }

    public function __destruct()
    {
        $pdo = null;
    }

    public static function prepMul(array $params): DB
    {
        $parNames = array_keys($params);
        $phArr = [];
        foreach ($params as $parName => $param){
            $i = 0;
            $phArr = [];
            foreach ($param as $p){
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

    private static function replaceData(string $tableName, array $params): object
    {
        return (object) [
            'sql' => self::getReplaceByUpdateQueryStr($tableName, $params),
            'params' => self::phParamsForUpd($params)
        ];
    }

    public static function initParams(object $object): array
    {
        $params = [];
        foreach ($object as $k => $v){
            //if($v === null) continue;

            if (is_array($v) || is_object($v)) {
                $v = json_encode($v);

            }

            $v = is_bool($object->$k) ? intval($v) : $v;
            $params[$k] = $v;
        }
        return $params;
    }

    private static function getReplaceByUpdateQueryStr(string $tableName, array $params): string
    {

        $parNamesStr = self::colNamesStr($params);
        $phNamesStr = self::valuesPhNamesStr($params);
        $paramsForUpdateStr = self::paramsForUpdateStr($params);

        return "insert into $tableName $parNamesStr VALUES $phNamesStr on duplicate key update $paramsForUpdateStr";
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

    public static function createNewID(string $tableName, string $keyColName = 'id') : int
    {

        $sql = "select max(id) + 1 as id from $tableName where $keyColName";
        global $DB;
        self::connect();

        $qwe = $DB->qwe($sql);
        if(!$qwe or !$qwe->rowCount()){
            return 1;
        }
        $q = $qwe->fetchObject();

        return $q->id ?? 1;
    }

    public static function implodeIntIn(array $ids): string
    {
        Helpers::isArrayIntList($ids)
            or throw new TypeError('array is not ints');
        return '(' . implode(',', $ids) . ')';
    }
}