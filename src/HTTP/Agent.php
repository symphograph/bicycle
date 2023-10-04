<?php

namespace Symphograph\Bicycle\HTTP;

class Agent
{
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
        if (PHP_SAPI === 'cli') {
            return new self();
        }
        $agent = get_browser();
        $Agent = new self();
        foreach (get_class_vars(self::class) as $k => $v){
            if(!isset($agent->$k)) continue;
            $Agent->$k = $agent->$k;
        }
        return $Agent;
    }

    public static function default(): self
    {
        return new self();
    }
}