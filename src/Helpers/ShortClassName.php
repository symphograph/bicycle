<?php

namespace Symphograph\Bicycle\Helpers;

use ReflectionClass;

trait ShortClassName
{
    public static function shortClassName(): string {
        return new ReflectionClass(static::class)->getShortName();
    }
}