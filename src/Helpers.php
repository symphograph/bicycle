<?php

namespace Symphograph\Bicycle;

use Exception;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\AppErr;
use TypeError;

class Helpers
{
    public static function dateToFirstDayOfMonth(string $date): bool|string
    {
        if(!self::isDate($date)){
            return false;
        }

        return date('Y-m-01',$date);
    }

    public static function isDate(string $date, string|array $format = 'Y-m-d'): bool
    {
        if(!is_array($format)){
            return date($format, strtotime($date)) === $date;
        }
        foreach ($format as $f){
            if(date($f, strtotime($date)) === $date)
                return true;
        }
        return false;
    }

    public static function isMyClassExist(string $className): bool
    {
        $fileName = str_replace('\\', '/', $className) . '.php';
        if(!file_exists(dirname(ServerEnv::DOCUMENT_ROOT()) . '/' . $fileName)){
            return false;
        }
        return class_exists($className);
    }

    /**
     * Get the class "basename" of the given object / class.
     *
     * @param  string|object  $class
     * @return string
     */
    public static function classBasename(object|string $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }

    /**
     * Принимает массив объектов и меняет его ключи на значение указанного поля
     */
    public static function colAsKey(array $List, string $key): array|bool
    {
        $arr = [];
        foreach ($List as $Object){
            if(!isset($Object->$key))
                return false;
            $arr[$Object->$key] = $Object;
        }
        return $arr;
    }

    public static function sanitizeName(string|null $str): string
    {
        if(empty($str)) return '';
        $str = trim($str);
        $str = preg_replace('/[^a-zA-ZА-Яа-я\-\s]/ui','',$str);
        $str = str_replace('&amp;', '&', $str);
        $str = str_replace('&nbsp;', ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        return $str ?? '';
    }

    public static function monthDaysList(int $year, int $month): array
    {
        $countMonthDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $monthDays = [];
        for($i = 1; $i <= $countMonthDays;$i++){
            $monthDays[$i] = 0;
        }
        return $monthDays;
    }

    public static function weekDaysOfMonth(int $year, int $month): array
    {

        $wdays = ['вс', 'пн', 'вт','ср','чт','пт','сб'];
        $days = self::monthDaysList($year, $month);
        $arr = [];

        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        foreach ($days as $day => $val){
            $zeroDay = str_pad($day, 2, '0', STR_PAD_LEFT);
            $wdayKey = date('w', strtotime("$year-$month-$zeroDay"));
            $arr[] = [
                'day'=> $day,
                'wday' => $wdays[$wdayKey]
                ];
        }
        return $arr;
    }

    /**
     * @throws Exception
     */
    public static function NickGenerator(int $locale = 0): string
    {
        $locale = 0;
        $keySpaces =
            [
                ['aeiou','bcdfghjklmnpqrstvwxyz','ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
                ['аеиоуыэюя','бвгджзклмнпрстфхчшщц','АБВГДЕЖЗИКЛМНОПРСТУФХЧШЩЦЫЭЮЯ']
            ];
        $nick = self::randomString(1,$keySpaces[$locale][2]);
        $r = random_int(3,19);
        for($i=0;$i<=$r;$i++)
        {
            $k = intval($i % 2 === 0);
            $nick .= self::randomString(1,$keySpaces[$locale][$k]);
        }
        return $nick;
    }

    /**
     * @throws Exception
     */
    public static function randomString($length, $keySpace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        $str = '';
        $max = mb_strlen($keySpace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keySpace[random_int(0, $max)];
        }
        return $str;
    }

    public static function median(array $arr): int|float|bool
    {

        if (!($count = count($arr))) {
            return false;
        }

        sort($arr);
        $middle = floor($count / 2);
        if ($count % 2){
            return round($arr[$middle]);
        }
        return round(($arr[$middle - 1] + $arr[$middle]) / 2);
    }

    public static function isArrayIntList(array $arr): bool
    {
        return array_is_list($arr) && self::isArrayInt($arr);
    }

    public static function isArrayInt(array $arr): bool
    {
        foreach ($arr as $a){
            if(!is_int($a))
                return false;
        }
        return true;
    }

    /**
     * @return bool
     * Return true if $array is string[]
     */
    public static function isArrayString(array $array): bool
    {
        foreach ($array as $value){
            if(!is_string($value)) return false;
        }
        return true;
    }

    /**
     * @param string[] $array
     * @param string $className
     * @return bool
     */
    public static function isArrayPropsOfClass(array $array, string $className): bool
    {
        if(!self::isArrayString($array)){
            throw new TypeError('$array is not string[]');
        }
        $classVars = get_class_vars($className);
        foreach ($array as $var){
            if(!array_key_exists($var, $classVars)){
                $className = self::class;
                return false;
            }
        }
        return true;
    }

    public static function isMultiArray(array $array): bool
    {
        return !!(count($array) - count($array, COUNT_RECURSIVE));
    }

    public static function isIntInRange($value, int $min, int $max): bool
    {
        return is_numeric($value) && ($min <= $value) && ($value <= $max);
    }

    public static function isExpired(string $datetime, ?int $timeZone = null): bool
    {
        if($timeZone === null){
            $timeZone = Env::getTimeZone();
        }
        return strtotime($datetime) < (time() + 3600 * $timeZone);
    }

    /**
     * @param array $Array
     * @param string $colName
     * @param $needle
     * @return array|object
     * First element of the filtered by ColValue Array
     */
    public static function arrayMultiSearch(array $Array, string $colName, $needle): array|object
    {
        $elements = array_filter($Array, fn($el) => $el->$colName === $needle);
        return array_shift($elements);
    }

    public static function arrayConcat(array $array1, array $array2, string $glue = ' '): array
    {
        if(!self::isArrayString($array1) || !self::isArrayString($array2)){
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
     * Определение правильной формы слова на основе числа.
     *
     * @param int $number Число, для которого нужно определить форму слова.
     * @param array $wordForms [яблоко, яблока, яблок]
     * @return string Правильная форма слова в зависимости от числа.
     */
    public static function numDeclension(int $number, array $wordForms = ['год', 'года', 'лет']): string
    {
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;

        // Правила склонения в русском языке для различных чисел.
        $cases = array(2, 0, 1, 1, 1, 2);

        // Определение формы слова на основе числа и контекста.
        $formIndex = ($lastTwoDigits > 4 && $lastTwoDigits < 20)
            ? 2
            : $cases[min($lastDigit, 5)];

        // Возвращение правильной формы слова из массива.
        return $wordForms[$formIndex];
    }

}