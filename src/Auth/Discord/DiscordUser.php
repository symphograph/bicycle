<?php

namespace Symphograph\Bicycle\Auth\Discord;

use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\DTO\SocialAccountDTO;

class DiscordUser extends SocialAccountDTO
{
    use DTOTrait;
    const tableName = 'user_discord';
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

}