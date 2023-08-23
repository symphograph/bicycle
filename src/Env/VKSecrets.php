<?php

namespace Symphograph\Bicycle\Env;

readonly class VKSecrets
{
    public function __construct(
        public int $appId,
        public string $privateKey,
        public string $serviceKey,
        public string $callback,
        public string $loginPageTitle
    )
    {
    }
}