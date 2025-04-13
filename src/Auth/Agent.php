<?php

namespace Symphograph\Bicycle\Auth;

use Symphograph\Bicycle\DTO\BindTrait;

class Agent
{
    use BindTrait;

    public string $browser_name_regex     = '';
    public string $browser_name_pattern   = '';
    public string $parent                 = '';
    public string $platform               = '';
    public string $comment                = '';
    public string $browser                = '';
    public string $browser_maker          = '';
    public string $device_type            = '';
    public string $device_pointing_method = '';
    public string $version                = '';
    public string $majorver               = '';
    public string $minorver               = '';
    public string $crawler                = '';
    public bool   $ismobiledevice         = false;
    public bool   $istablet               = false;


    public static function getSelf(): self
    {
        $agent = get_browser();
        return self::byBind($agent);
    }

    public static function default(): self
    {
        return new self();
    }
}