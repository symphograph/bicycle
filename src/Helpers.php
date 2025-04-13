<?php

namespace Symphograph\Bicycle;

use Exception;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;

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
        return array_any($format, fn($f) => date($f, strtotime($date)) === $date);
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

    public static function sanitizeName(string|null $str): string
    {
        if(empty($str)) return '';
        $str = trim($str);
        $str = preg_replace('/[^a-zA-ZА-Яа-яёЁ\-\s]/ui','',$str);
        $str = str_replace('&amp;', '&', $str);
        $str = str_replace('&nbsp;', ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        return $str ?? '';
    }

    public static function monthDaysList(int $year, int $month): array
    {
        $countMonthDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        return array_fill(1, $countMonthDays - 1 + 1, 0);
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

    /**
     * @return bool
     * Return true if $array is string[]
     */
    public static function isArrayString(array $array): bool
    {
        return !empty($array) && array_all($array, fn($value) => is_string($value));
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

    /**
     * Объединяет элементы в строку, разделяя запятой и словом 'and' перед последним элементом
     * @param string[] $words
     * @return string
     */
    function joinWordsWithAnd(array $words): string
    {
        if (count($words) < 2) {
            return implode(', ', $words);
        }

        $lastWord = array_pop($words);
        return implode(', ', $words) . ' and ' . $lastWord;
    }

}