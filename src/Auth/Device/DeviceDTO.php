<?php

namespace Symphograph\Bicycle\Auth\Device;


use Symphograph\Bicycle\Auth\DTOCookieTrait;
use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\PDO\DB;

class DeviceDTO
{
    use DTOTrait;
    use DTOCookieTrait;

    const string tableName  = 'devices';
    const string cookieName = 'Mozart';

    public int    $id;
    public string $marker;
    public string $createdAt;
    public string $visitedAt;
    public string $platform;
    public bool   $ismobiledevice;
    public string $browser;
    public string $fingerPrint;
    public string $device_type;
    public string $firstIp;
    public string $lastIp;

    public static function bySessId(int $sessId): static
    {
        $sql = "
            select devices.* from devices 
            inner join sessions
            on devices.id = sessions.deviceId
            and sessions.id = :sessId";
        $params = ['sessId' => $sessId];
        $qwe = DB::qwe($sql, $params);
        return $qwe->fetchObject(static::class);
    }

    public function linkToUser(int $userId): void
    {
        $sql = "REPLACE INTO device_user (deviceId, userId) VALUES (:deviceId, :userId)";
        $params = ['deviceId' => $this->id, 'userId' => $userId];
        DB::qwe($sql, $params);
    }

    public function unlinkFromUser(int $userId): void
    {
        $sql = "DELETE FROM device_user WHERE deviceId = :deviceId AND userId = :userId";
        $params = ['deviceId' => $this->id, 'userId' => $userId];
        DB::qwe($sql, $params);
    }

    public function linkToAccount(int $accountId): void
    {
        $sql = "REPLACE INTO device_account (deviceId, accountId) VALUES (:deviceId, :accountId)";
        $params = ['deviceId' => $this->id, 'accountId' => $accountId];
        DB::qwe($sql, $params);
    }

    public function unlinkFromAccount(int $accountId): void
    {
        $sql = "DELETE FROM device_account WHERE deviceId = :deviceId AND accountId = :accountId";
        $params = ['deviceId' => $this->id, 'accountId' => $accountId];
        DB::qwe($sql, $params);
    }
}