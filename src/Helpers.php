<?php

namespace Symphograph\Bicycle;

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
        if(!file_exists(dirname($_SERVER['DOCUMENT_ROOT']) . '/classes/' . $fileName)){
            return false;
        }
        return class_exists($className);
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

}