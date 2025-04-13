<?php

namespace Symphograph\Bicycle\Auth\Account\Profile\Yandex;

readonly class YandexSecrets
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly string $suggestKey
    )
    {
    }

}