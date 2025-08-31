<?php

namespace Symphograph\Bicycle\Auth\Device;

use Symphograph\Bicycle\Auth\Agent;
use Symphograph\Bicycle\Auth\ModelCookieTrait;
use Symphograph\Bicycle\DTO\ModelTrait;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\Helpers\Date;
use Symphograph\Bicycle\HTTP\Cookie;
use Symphograph\Bicycle\Logs\ErrorLog;
use Symphograph\Bicycle\PDO\DB;
use Symphograph\Bicycle\PDO\PutMode;

class Device extends DeviceDTO
{
    use ModelTrait;
    use ModelCookieTrait;
    const int cookDuration = 31536000;

    public static function byCookie(): ?self
    {
        if(empty($_COOKIE[self::cookieName])){
            throw new AuthErr('cook is empty');
        }
        $Device = self::byMarker($_COOKIE[self::cookieName]);
        if(!$Device){
            self::unsetCookie();
            throw new AuthErr('Unknown device', 'Unknown device');
        }
        return $Device;
    }

    public static function createOrUpdate(): self
    {
        if (empty($_COOKIE[self::cookieName])) {
            $device = self::create();
            $device->setCookieDevice();
            return $device;
        }

        $Device = Device::byMarker($_COOKIE[self::cookieName]);
        if (!$Device) {
            ErrorLog::writeMsg('device does not exist');
            $device = self::create();
            $device->setCookieDevice();
            return $device;
        }

        $Device->update();
        return $Device;
    }

    public static function create(): self
    {
        $agent = Agent::getSelf();

        $Device = new self();
        $Device->marker = self::createMarker();
        $time = date('Y-m-d H:i:s');
        $Device->createdAt = $time;
        $Device->visitedAt = $time;
        $Device->fingerPrint = self::createFingerPrint();
        $Device->platform = $agent->platform;
        $Device->ismobiledevice = $agent->ismobiledevice;
        $Device->browser = $agent->browser;
        $Device->device_type = $agent->device_type;
        $Device->firstIp = $_SERVER['REMOTE_ADDR'];
        $Device->lastIp = $_SERVER['REMOTE_ADDR'];
        $Device->putToDB(PutMode::insert);
        $Device->id = DB::lastId();
        return $Device;
    }

    public function setCookieDevice(): void
    {
        $domain = '.' . ServerEnv::SERVER_NAME();
        $opts = Cookie::opts(expires: self::cookDuration, samesite: 'None', domain: $domain);
        Cookie::set(self::cookieName, $this->marker, $opts);
    }

    public function update(): void
    {
        $this->visitedAt = date('Y-m-d H:i:s');
        $this->fingerPrint = self::createFingerPrint();
        $this->lastIp = $_SERVER['REMOTE_ADDR'];
        $this->putToDB();
        $this->setCookieDevice();
    }

    protected function datesToISO_8601(): void
    {
        $this->createdAt = Date::dateFormatFeel($this->createdAt, 'c');
        $this->visitedAt = Date::dateFormatFeel($this->visitedAt, 'c');
    }

    public static function createFingerPrint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $clientFingerPrint = $_SERVER['HTTP_FINGERPRINT'];

        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';


        $fingerprintData = $userAgent . $ipAddress . $acceptLanguage . $encoding . $clientFingerPrint;

        return hash('sha256', $fingerprintData);
    }

}