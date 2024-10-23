<?php

namespace Symphograph\Bicycle\Helpers;

class Str
{
    public static function camel(string $string, bool $upFirstChar = false): string
    {

        $str = str_replace(['_', ' '], '-',$string);
        $str = ucwords($str, '-');
        $str = str_replace('-', '', $str);

        return $upFirstChar ? $str : lcfirst($str);
    }

    public static function mb_ucfirst(string $str, string $encoding = 'UTF-8'): string
    {
        $firstChar = mb_substr($str, 0, 1, $encoding);
        $restOfString = mb_substr($str, 1, null, $encoding);
        return mb_strtoupper($firstChar, $encoding) . $restOfString;
    }
}