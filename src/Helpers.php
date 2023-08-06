<?php

namespace Symphograph\Bicycle;

use Symphograph\Bicycle\Env\Env;

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
        if(!file_exists(dirname($_SERVER['DOCUMENT_ROOT']) . '/' . $fileName)){
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
    public static function classBasename($class): string
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

    public static function sanitazeName(string|null $str): string
    {
        if(empty($str)) return '';
        $str = trim($str);
        $str = preg_replace('/[^a-zA-ZА-Яа-я\-\s]/ui','',$str);
        $str = str_replace('&amp;', '&', $str);
        $str = str_replace('&nbsp;', ' ', $str);
        $str = preg_replace('!\s++!u', ' ', $str);
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
     * @throws \Exception
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
     * @throws \Exception
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

    public static function median(array $arr): int|bool
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
        return self::isArrayInt($arr) && array_is_list($arr);
    }

    public static function isArrayInt(array $arr): bool
    {
        foreach ($arr as $a){
            if(!is_int($a))
                return false;
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
}