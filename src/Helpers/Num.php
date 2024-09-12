<?php

namespace Symphograph\Bicycle\Helpers;

class Num
{
    public static function zeroFill($number, $length = 3): string
    {
        return str_pad($number, $length, '0', STR_PAD_LEFT);
    }

    /**
     * @param int[]|float[] $arr
     * @param int|float $x
     * @return int[]|float[]
     */
    public static function arrX(array $arr, int|float $x): array
    {
        return array_map(fn($val) => $val * $x, $arr);
    }

    /**
     * @param int[]|float[] $arr
     * @param int|float $x
     * @return int[]|float[]
     */
    public static function arrDivX(array $arr, int|float $x): array
    {
        return array_map(fn($val) => $val / $x, $arr);
    }


}