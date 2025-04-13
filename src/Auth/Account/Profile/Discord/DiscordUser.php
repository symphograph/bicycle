<?php

namespace Symphograph\Bicycle\Auth\Account\Profile\Discord;

use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\Auth\Account\Profile\AccProfileDTO;
use Symphograph\Bicycle\PDO\DB;

class DiscordUser extends AccProfileDTO
{
    use DTOTrait;
    const string tableName = 'user_discord';

    public int     $id;
    public string  $username;
    public ?string $display_name;
    public ?string $avatar;
    public ?string $avatar_decoration;
    public int     $discriminator;
    public ?int    $public_flags;
    public ?int    $flags;
    public ?string $banner;
    public ?int    $banner_color;
    public ?int    $accent_color;
    public ?string $locale;
    public bool    $verified    = false;
    public ?string $email;
    public ?int    $premium_type;
    public bool    $mfa_enabled = false;
    //public bool    $system = false;
    public bool $bot = false;

    private static function byUserName(string $username): ?self
    {
        $qwe = DB::qwe("select * from user_discord where username = :username", ['username' => $username]);
        return $qwe->fetchObject(self::class);
    }

    public static function byContact(string $contactValue): ?self
    {
        return self::byUserName($contactValue);
    }


    public function externalAvaUrl(): string
    {
        return "https://cdn.discordapp.com/avatars/$this->id/$this->avatar.png";
    }

    public function nickName(): string
    {
        return $this->display_name ?? '' ?: $this->username;
    }
}