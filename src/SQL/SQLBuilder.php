<?php

namespace Symphograph\Bicycle\SQL;

use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Helpers;
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
        if(!Helpers::isArrayPropsOfClass($cols, $className)){
            throw new TypeError('orderBy is Not Props of Class');
        }
        $arr = Helpers::arrayConcat($cols, $directions);

        return implode(', ', $arr);
    }

    private static function isDirection(string $direction): bool
    {
        return in_array(mb_strtolower($direction), ['asc', 'desc']);
    }
}