<?php

namespace Symphograph\Bicycle\SQL;

use InvalidArgumentException;
use ReflectionClass;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Helpers\Arr;
use TypeError;

class SQLBuilder
{

    public function __construct(
        public int $startId = 0,
        public array $order = [],
        public int $limit = 0,
    )
    {

    }

    public static function orderByArr(string $className, string|array $orderBy): string
    {
        $classVars = get_class_vars($className);
        $whiteList = array_keys($classVars);
        $whiteList[] = 'rand()';
        $orderByList = [];

        foreach ($orderBy as $key => $value){
            if(in_array(mb_strtolower($value), ['asc', 'desc'])){
                $valName = $key;
                $direction = $value;
            }else{
                $valName = $value;
                $direction = 'ASC';
            }
            if(!in_array($valName, $whiteList)){
                throw new AppErr('Invalid arguments for orderBy');
            }
            $orderByList[] = "$valName $direction";
        }

        return implode(', ', $orderByList);
    }

    public static function orderBy(string $className, string $orderBy): string
    {
        if($orderBy === 'rand()'){
            return 'rand()';
        }
        $params = explode(',', $orderBy);
        $params = array_map('trim',$params);
        $cols = [];
        $directions = [];
        foreach ($params as $param){
            $param = explode(' ', $param);
            $cols[] = trim($param[0]);
            $direction = trim($param[1] ?? 'asc') ;
            $directions[] = $direction;
            self::isDirection($direction)
                or throw new TypeError('Invalid direction in OrderBy');
        }
        if(!Arr::isArrayPropsOfClass($cols, $className)){
            throw new TypeError('orderBy is Not Props of Class');
        }
        $arr = Arr::arrayConcat($cols, $directions);

        return implode(', ', $arr);
    }

    private static function isDirection(string $direction): bool
    {
        return in_array(mb_strtolower($direction), ['asc', 'desc']);
    }

    public static function doubles(string $tableName, string $colName): string
    {
        return "SELECT *
        FROM $tableName
        WHERE $colName IN (
            SELECT $colName
            FROM $tableName
            WHERE $colName IS NOT NULL AND $colName != ''
            GROUP BY $colName
            HAVING COUNT(*) > 1
            )";

    }

    public static function createByClass(string $className): string
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException("Class $className does not exist.");
        }

        $reflectionClass = new ReflectionClass($className);
        $properties = $reflectionClass->getProperties();

        $tableName = $reflectionClass->getConstant('tableName');
        if (!$tableName) {
            throw new InvalidArgumentException("Class $className does not have a tableName constant.");
        }

        $colId = $reflectionClass->getConstant('colId') ?: 'id';
        $fields = [];
        $indexes = [];
        $hasPrimaryKey = false;

        foreach ($properties as $property) {
            $fieldName = $property->getName();
            $type = $property->getType();
            $typeName = strtolower($type->getName());

            if ($typeName === 'int') {
                $fieldType = 'INT';
            } elseif ($typeName === 'string') {
                // Check if the field is for HTML content
                if ($fieldName === 'html') {
                    $fieldType = 'LONGTEXT';
                } else {
                    $fieldType = 'VARCHAR(255)';
                }
            } else {
                throw new InvalidArgumentException("Unsupported property type: " . $type->getName());
            }

            $nullability = $type->allowsNull() ? '' : 'NOT NULL';

            if ($fieldName === 'id' && $fieldType === 'INT') {
                $fields[] = "$fieldName $fieldType AUTO_INCREMENT PRIMARY KEY $nullability";
                $hasPrimaryKey = true;
            } else {
                $fields[] = "$fieldName $fieldType $nullability";
                if ($fieldName === $colId) {
                    $hasPrimaryKey = true;
                }
                // Adding index for fields except the primary key
                if ($fieldName !== $colId) {
                    $indexes[] = "INDEX idx_$fieldName ($fieldName)";
                }
            }
        }

        // If the primary key field is specified in colId and it is not 'id', add it as the primary key
        if ($hasPrimaryKey && $colId !== 'id') {
            $fields[] = "PRIMARY KEY ($colId)";
        }

        $sql = "CREATE TABLE IF NOT EXISTS $tableName (\n";
        $sql .= "    " . implode(",\n    ", $fields);

        // Adding indexes if any
        if (!empty($indexes)) {
            $sql .= ",\n    " . implode(",\n    ", $indexes);
        }

        $sql .= "\n);";

        return $sql;
    }
}