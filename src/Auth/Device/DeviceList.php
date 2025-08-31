<?php

namespace Symphograph\Bicycle\Auth\Device;

use Symphograph\Bicycle\DTO\AbstractList;

class DeviceList extends AbstractList
{
    /**
     * @var Device[]
     */
    public array $list = [];

    public static function getItemClass(): string
    {
        return Device::class;
    }

    public static function all(): static
    {
        $sql = "SELECT * FROM devices";
        return static::bySql($sql);
    }

    public static function byUserId(int $userId): static
    {
        $sql = "SELECT devices.* FROM devices 
            inner join device_user du 
            on devices.id = du.deviceId
            and du.userId = :userId";

        $params = ['userId' => $userId];
        return static::bySql($sql, $params);
    }

    /**
     * @return Device[]
     */
    public function getList(): array
    {
        return $this->list;
    }
}