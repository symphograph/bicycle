<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Env\Env;

class CurlToken
{
    public static function create(
        array  $powers = [],
        string $authType = 'server',
        string $ussuer = ''
    ): string
    {
        $jwtEnv = Env::getJWT();
        $audience = $jwtEnv->audience;

        $audience[] = $_SERVER['SERVER_NAME'];
        $Token = new Token(
            jti: 'any',
            uid: $jwtEnv->uid,
            accountId: $jwtEnv->accountId,
            aud: $audience,
            expireDuration: '+1 min',
            powers: $powers,
            authType: $authType,
            iss: $jwtEnv->issuer
        );
        return $Token->jwt;
    }
}