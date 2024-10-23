<?php

namespace Symphograph\Bicycle\Auth\Mailru;

use Symphograph\Bicycle\Auth\OAuthSecrets;

readonly class MailruSecrets implements OAuthSecrets
{
    public function __construct(
        public string $app_id,
        public string $app_secret
    ){}

    public function getAppId(): string
    {
        return $this->app_id;
    }

    public function getKey(): string
    {
        return $this->app_secret;
    }
}