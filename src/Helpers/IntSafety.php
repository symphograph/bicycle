<?php

namespace Symphograph\Bicycle\Helpers;

class IntSafety
{
    const string max = '9223372036854775807'; // Для 64-bit

    public static function isSafeAdd($a, $b): bool
    {
        $aStr = (string)$a;
        $bStr = (string)$b;

        return bccomp($aStr, self::max) <= 0
            && bccomp($bStr, bcsub(self::max, $aStr)) <= 0;
    }
}