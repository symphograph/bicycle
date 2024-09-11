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
}