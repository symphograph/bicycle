<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Env\Env;

class AccessToken
{
    public static function create(
        int    $uid,
        int    $accountId,
        array  $powers = [],
        string $createdAt = 'now',
        string $authType = 'default',
        string $avaFileName = 'init_ava.jpg',
    ): string
    {
        $audience = Env::getJWT()->audience;

        $audience[] = $_SERVER['SERVER_NAME'];
        $Token = new Token(
            jti: 'any',
            uid: $uid,
            accountId: $accountId,
            aud: $audience,
            createdAt: $createdAt,
            expireDuration: '+1 day',
            powers: $powers,
            authType: $authType,
            avaFileName: $avaFileName
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