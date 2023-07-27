<?php

namespace Symphograph\Bicycle\Env;

readonly class TelegramSecrets
{
    public function __construct(public string $token, public string $bot_name, public string $loginPageTitle)
    {
    }
}