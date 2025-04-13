<?php

namespace Symphograph\Bicycle\Auth\Account\Profile\Telegram;

use Symphograph\Bicycle\PDO\DB;
use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\Auth\Account\Profile\AccProfileDTO;

class TeleUserDTO extends AccProfileDTO
{
    use DTOTrait;

    const string tableName = 'user_telegram';

    public int    $id         = 0;
    public string $first_name = '';
    public string $last_name  = '';
    public string $username   = '';
    public string $photo_url  = '';
    public int    $auth_date  = 0;

    public static function byUserName(string $username): ?static
    {
        $qwe = DB::qwe("select * from user_telegram where username = :username", ['username' => $username]);
        return $qwe?->fetchObject(self::class) ?: null;
    }

    public static function byContact(string $contactValue): ?static
    {
        return static::byUserName($contactValue);
    }

    public function externalAvaUrl(): string
    {
        return $this->photo_url;
    }

    public function nickName(): string
    {
        return static::nickByNames() ?: $this->username;
    }
}