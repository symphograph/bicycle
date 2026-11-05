<?php

namespace Symphograph\Bicycle\Helpers;

class CpuTracker
{
    const int msMul = 1_000_000;

    public static function cur(): int
    {
        $usage = getrusage();
        $mul = self::msMul;
        $user = $usage['ru_utime.tv_sec'] * $mul + $usage['ru_utime.tv_usec'];
        $system = $usage['ru_stime.tv_sec'] * $mul + $usage['ru_stime.tv_usec'];
        return $user + $system;
    }
}