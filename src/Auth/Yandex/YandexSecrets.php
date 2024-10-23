<?php

namespace Symphograph\Bicycle\Auth\Yandex;

use Symphograph\Bicycle\Auth\OAuthSecrets;

readonly class YandexSecrets implements OAuthSecrets
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public string $callback,
        public string $loginPageTitle,
        public string $suggestKey
    )
    {
    }

    public function getAppId(): string
    {
        return $this->clientId;
    }

    public function getKey(): string
    {
        return $this->clientSecret;
    }
}