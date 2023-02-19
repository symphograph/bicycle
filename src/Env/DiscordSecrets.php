<?php

namespace Symphograph\Bicycle\Env;

readonly class DiscordSecrets
{
    public function __construct(
        public string $clientId,
        public string $clientSecret
    )
    {
    }
}