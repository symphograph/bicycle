<?php

namespace Symphograph\Bicycle\Contact;

enum Gender: int
{
    case male = 1;
    case female = 2;
    case neuter = 3;

    public function labelRu(): string
    {
        return match ($this) {
            self::male => 'он',
            self::female => 'она',
            self::neuter => 'оно'
        };
    }
}
