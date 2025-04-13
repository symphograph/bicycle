<?php

namespace Symphograph\Bicycle\Auth\Device;

use Symphograph\Bicycle\DTO\AbstractList;

class DeviceList extends AbstractList
{

    public static function getItemClass(): string
    {
        return Device::class;
    }

    public static function all(): static
    {
        $sql = "SELECT * FROM devices";
        return static::bySql($sql);
    }
}