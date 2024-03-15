<?php

namespace Symphograph\Bicycle\Helpers;

class NumberHelper
{
    public static function zeroFill($number, $length = 3): string
    {
        return str_pad($number, $length, '0', STR_PAD_LEFT);
    }
}