<?php

namespace Symphograph\Bicycle\Helpers;

class Secure
{
    public static function createShortCode(int $len = 5): string
    {
        $alphabet = '23456789ABCDEFGHKMNPRSTUVWXYZ';
        $idxRange = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < $len; $i++) {
            $idx = random_int(0, $idxRange);
            $code .= $alphabet[$idx];
        }

        return $code;
    }
}