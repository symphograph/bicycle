<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Env\Env;

class CurlToken
{
    public static function create(
        int    $uid,
        array  $powers = [],
        string $authType = 'curl'
    ): string
    {
        $audience = Env::getJWT()->audience;

        $audience[] = $_SERVER['SERVER_NAME'];
        $Token = new Token(
            jti: 'any',
            uid: $uid,
            aud: $audience,
            expireDuration: '+1 min',
            powers: $powers,
            authType: $authType
        );
        return $Token->jwt;
    }
}