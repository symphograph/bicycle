<?php

namespace Symphograph\Bicycle\Env;

readonly class YandexSecrets
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public string $callback,
        public string $loginPageTitle
    )
    {
    }
}