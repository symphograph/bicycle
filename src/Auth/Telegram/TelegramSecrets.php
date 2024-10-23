<?php

namespace Symphograph\Bicycle\Auth\Telegram;

use Symphograph\Bicycle\Auth\OAuthSecrets;

readonly class TelegramSecrets implements OAuthSecrets
{
    public function __construct(
        private string $token,
        private string $bot_name
    ){}

    public function getAppId(): string
    {
        return $this->bot_name;
    }

    public function getKey(): string
    {
        return $this->token;
    }
}