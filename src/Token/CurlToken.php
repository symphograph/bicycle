<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;

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

        $audience[] = ServerEnv::SERVER_NAME();
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