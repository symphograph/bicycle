<?php

namespace Symphograph\Bicycle\Auth\Discord;

use Symphograph\Bicycle\Auth\OAuthSecrets;

readonly class DiscordSecrets implements OAuthSecrets
{
    public function __construct(
        public string $clientId,
        public string $clientSecret
    ){}

    public function getAppId(): string
    {
        return $this->clientId;
    }

    public function getKey(): string
    {
        return $this->clientSecret;
    }
}