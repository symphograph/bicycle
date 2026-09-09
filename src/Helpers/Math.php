<?php

namespace Symphograph\Bicycle\Helpers;

class Math
{

    /**
     * Наибольший общий делитель
     */
    public static function greatestComDivisor(int $a, int $b) : int
    {
        while ($b != 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }
        return $a;
    }

    /**
     * Возвращает упрощенное соотношение сторон
     * @return int[]
     */
    public static function aspectRatio(int $width, int $height, $tolerance = 0.01): array
    {
        $gcd = self::greatestComDivisor($width, $height);
        $aspectRatio = [$width / $gcd, $height / $gcd];

        $standardRatios = [
            [16, 9], [9, 16],  // Альбомная и портретная ориентация для HD, Full HD, UHD
            [4, 3], [3, 4],    // Традиционное телевизионное соотношение и его портретный вариант
            [3, 2], [2, 3],    // Мобильные устройства, классическая фотография
            [21, 9], [9, 21],  // Ультраширокое и узкое вертикальное соотношение
            [1, 1],            // Квадратное соотношение
            [5, 4], [4, 5],    // Старые мониторы и вертикальный вариант
            [18, 9], [9, 18],  // Современные смартфоны
            [32, 9], [9, 32],  // Сверхширокое и сверхузкое вертикальное соотношение
            [17, 9], [9, 17],  // Цифровое кинематографическое соотношение
            // Другие соотношения по желанию
        ];

        foreach ($standardRatios as $standardRatio) {
            $ratioDiff = abs($aspectRatio[0]/$aspectRatio[1] - $standardRatio[0]/$standardRatio[1]);
            if ($ratioDiff <= $tolerance) {
                return $standardRatio;
            }
        }

        rsort($aspectRatio);
        return $aspectRatio;
    }

    public static function median(array $arr): int|float
    {
        $count = count($arr);
        if (!$count) return 0;

        sort($arr);
        $middle = floor($count / 2);
        if ($count % 2){
            return round($arr[$middle]);
        }
        return round(($arr[$middle - 1] + $arr[$middle]) / 2);
    }

    public static function isBetween(int $value, int $min, int $max): bool
    {
        return $min <= $value && $value <= $max;
    }

    /**
     * ($val1 - $val2 + $len) % $len
     */
    public static function difInfinity(int $val1, $val2, int $len): int
    {
        return ($val1 - $val2 + $len) % $len;
    }

    public static function isEven(int $val): bool
    {
        return ($val & 1) === 0;
    }
}