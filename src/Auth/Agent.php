<?php

namespace Symphograph\Bicycle\Auth;

use Symphograph\Bicycle\DTO\BindTrait;
use WhichBrowser\Parser;

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
    public bool   $isbot                  = false;


    /*
    public static function getSelf(): self
    {
        $agent = get_browser();
        return self::byBind($agent);
    }
    */

    public static function default(): self
    {
        return new self();
    }

    public static function getSelf(): self
    {
        $agentStr = self::agentStr();
        $parser = new Parser($agentStr);

        $agent = new self();
        $agent->browser = $parser->browser->name ?? '';
        $agent->browser_maker = $parser->browser->manufacturer ?? '';
        $agent->platform = $parser->os->name ?? '';
        $agent->version = $parser->browser->version->value ?? '';
        $agent->majorver = strstr($agent->version, '.', true) ?: $agent->version;
        $agent->minorver = $parser->browser->version->minor ?? '';
        $agent->ismobiledevice = $parser->device->type === 'mobile';
        $agent->istablet = $parser->device->type === 'tablet';
        $agent->isbot = $parser->device->type === 'bot';
        $agent->device_type = $parser->device->type ?? 'desktop';

        return $agent;
    }

    private static function agentStr(): string
    {
        // Основные источники User-Agent
        $sources = [
            'HTTP_USER_AGENT',
            'USER_AGENT',
            'HTTP_X_ORIGINAL_USER_AGENT', // иногда используется в прокси
            'HTTP_X_OPERAMINI_PHONE_UA', // для Opera Mini
        ];

        foreach ($sources as $source) {
            if (!empty($_SERVER[$source])) {
                return $_SERVER[$source];
            }
        }

        return PHP_SAPI === 'cli' ? 'CLI' : 'Unknown';
    }
}