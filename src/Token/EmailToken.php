<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Env\Server\ServerEnv;


class EmailToken
{
    public static function create(string $email, int $minBeforeExpired): string
    {
        $claims = [
            'email' => $email,
        ];
        $Token = new Token(
            aud: [ServerEnv::SERVER_NAME()],
            createdAt: 'now',
            expireDuration: "+$minBeforeExpired min",
            claims: $claims,
        );
        return $Token->jwt;
    }


}