<?php

namespace Symphograph\Bicycle\Auth;

interface OAuthSecrets
{
    public function getAppId();
    public function getKey();
}