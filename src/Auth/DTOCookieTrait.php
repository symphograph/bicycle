<?php

namespace Symphograph\Bicycle\Auth;


use Symphograph\Bicycle\Env\Config;
use Symphograph\Bicycle\HTTP\Cookie;
use Symphograph\Bicycle\PDO\DB;

trait DTOCookieTrait
{
    public static function byMarker(string $marker): ?self
    {
        $tableName = self::tableName;
        $qwe = DB::qwe("select * from $tableName where marker = :marker", ['marker' => $marker]);
        return $qwe?->fetchObject(self::class) ?: null;
    }

    public function setCookie(int $duration = 0, $path = '/', $partitioned = false): void
    {
        $opts = Cookie::opts(expires: $duration,path: $path, samesite: 'Strict', partitioned: $partitioned);
        Cookie::set(self::cookieName, $this->marker, $opts);
    }

    public static function unsetCookie(): void
    {
        $opts = Config::cookOpts(expires: -3600 * 24 * 366);
        setcookie(self::cookieName, '', $opts);
        unset($_COOKIE[self::cookieName]);
    }

    public static function createMarker(): string
    {
        return bin2hex(random_bytes(12));
    }
}
