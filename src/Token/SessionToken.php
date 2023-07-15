<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Errors\AuthErr;

class SessionToken
{
    public static function create(string $sessionId, string $createdAt = 'now'): string
    {
        $Token = new Token(
            jti: $sessionId,
            aud: [$_SERVER['SERVER_NAME']],
            createdAt: $createdAt,
            expireDuration: '+1 month'
        );
        return $Token->jwt;
    }

    public static function validation(string $jwt): void
    {
        Token::validation(jwt: $jwt);
    }
}