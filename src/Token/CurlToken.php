<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;

class CurlToken
{
    public static function create(
        array  $powers = [],
    ): string
    {
        $jwtEnv = Env::getJWT();
        $audience = $jwtEnv->audience;

        $claims = [
            'powers'      => $powers,
            'authType'    => 'server',
        ];

        $audience[] = ServerEnv::SERVER_NAME();
        $Token = new Token(
            uid: $jwtEnv->uid,
            aud: $audience,
            expireDuration: '+1 min',
            iss: ServerEnv::SERVER_NAME(),
            claims: $claims
        );
        return $Token->jwt;
    }
}