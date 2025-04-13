<?php

namespace Symphograph\Bicycle\Helpers;

use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use TypeError;

class Arr
{
    public static function filter(array $Array, string $propName, string|int|float|bool $propValue, int $len = 0): array
    {
        $elements = array_filter($Array, fn($el) => $el->$propName === $propValue);
        if($len === 0) {
            return $elements;
        }

        return $len ? $elements : array_slice($elements, 0, $len);
    }

    public static function listOfProp(array $array,string $propName): array
    {
        $arr = [];
        foreach ($array as $item) {
            $arr[] = $item->$propName;
        }
        return $arr;
    }

    /**
     * Sorts an array of object by props
     */
    public static function sortMultiArrayByProp($array, array $args = ['votes' => 'desc']): array
    {
        usort($array, function ($a, $b) use ($args) {
            $res = 0;

            $a = (object)$a;
            $b = (object)$b;

            foreach ($args as $k => $v) {
                $res = $a->$k <=> $b->$k;
                if(!$res) continue;
                if ($v == 'desc') $res = -$res;
                break;
            }

            return $res;
        });

        return $array;
    }


    /**
     * @param Object[] $List
     * @param string $key
     * @return Object[]
     *  Принимает массив объектов и меняет его ключи на значения указанного поля
     */
    public static function colAsKey(array $List, string $key): array
    {
        if(empty($List)) return [];

        $arr = [];
        foreach ($List as $Object){
            if(!isset($Object->$key)){
                throw new AppErr("Prop $key not found in Object");
            }

            $arr[$Object->$key] = $Object;
        }
        return $arr;
    }

    public static function isMulti(array $array): bool
    {
        return !!(count($array) - count($array, COUNT_RECURSIVE));
    }

    public static function isArrayIntList(array $arr): bool
    {
        return array_is_list($arr) && self::isInts($arr);
    }

    public static function isInts(array $arr): bool
    {
        return !empty($arr) && array_all($arr, fn($a) => is_int($a));
    }

    public static function arrayConcat(array $array1, array $array2, string $glue = ' '): array
    {
        if(!self::isStrings($array1) || !self::isStrings($array2)){
            throw new TypeError('invalid type of array values');
        }
        if(!array_is_list($array1) || !array_is_list($array2)){
            throw new TypeError('array must be a list');
        }

        $master = $array1;
        $slave = $array2;
        if(count($array1) < count($array2)) {
            $master = $array2;
            $slave = $array1;
        }

        $result = [];
        foreach ($master as $k => $value){
            $result[] =
                isset($slave[$k])
                    ? $value . $glue . $slave[$k]
                    : $value;
        }
        return $result;
    }

    /**
     * @return bool
     * Return true if $array is string[]
     */
    public static function isStrings(array $array): bool
    {
        return !empty($array) && array_all($array, fn($value) => is_string($value));
    }

    /**
     * @param string[] $array
     * @param string $className
     * @return bool
     */
    public static function isArrayPropsOfClass(array $array, string $className): bool
    {
        if(empty($array)) return false;

        if(!self::isStrings($array)){
            throw new TypeError('$array is not string[]');
        }
        $classVars = get_class_vars($className);
        return array_all($array, fn($var) => array_key_exists($var, $classVars));
    }

    public static function isEqual(array $array1, array $array2): bool
    {
        if(count($array1) !== count($array2)) return false;

        ksort($array1);
        ksort($array2);
        return $array1 === $array2;
    }
}