<?php

namespace Symphograph\Bicycle\Helpers;

enum UpDown: int
{
    case up = 1;
    case down = -1;
    case static = 0;

    public function isUp(): bool
    {
        return $this === self::up;
    }

    public function isDown(): bool
    {
        return $this === self::down;
    }
}
