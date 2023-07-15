<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Env\Env;

class AccessToken
{
    public static function create(array $powers = [], string $createdAt = 'now'): string
    {
        $audience = Env::getJWT()->audience;
        $audience[] = $_SERVER['SERVER_NAME'];
        $Token = new Token(
            jti: 'any',
            aud: $audience,
            createdAt: $createdAt,
            expireDuration: '+1 hour',
            powers: $powers
        );
        return $Token->jwt;
    }

    public static function validation(string $jwt, $needPowers = [], bool $ignoreExpire = false): void
    {
        Token::validation(
            jwt: $jwt,
            needPowers: $needPowers,
            ignoreExpire: $ignoreExpire
        );
    }

}