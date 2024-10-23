<?php

namespace Symphograph\Bicycle\Auth\Vkontakte;

use Symphograph\Bicycle\Auth\OAuthSecrets;

readonly class VKSecrets implements OAuthSecrets
{
    public function __construct(
        public int    $appId,
        public string $privateKey,
        public string $serviceKey,
        public string $callback,
        public string $loginPageTitle,
        public string $codeRedirect,
        public string $longToken
    ){}

    public function getAppId(): int
    {
        return $this->appId;
    }

    public function getKey(): string
    {
        return $this->privateKey;
    }
}