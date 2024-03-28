<?php

namespace Symphograph\Bicycle\Auth\Telegram;

use Symphograph\Bicycle\PDO\DB;
use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\DTO\SocialAccountDTO;

class TeleUserDTO extends SocialAccountDTO
{
    use DTOTrait;

    const string tableName = 'user_telegram';

    public int    $id         = 0;
    public string $first_name = '';
    public string $last_name  = '';
    public string $username   = '';
    public string $photo_url  = '';
    public int    $auth_date  = 0;

    public static function byUserName(string $username): self|false
    {
        $qwe = DB::qwe("select * from user_telegram where username = :username", ['username' => $username]);
        return $qwe->fetchObject(self::class);
    }

    public static function byContact(string $contactValue): self|false
    {
        return self::byUserName($contactValue);
    }
}