<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;

class SessionToken
{
    /**
     * @throws AuthErr
     */
    public static function create(string $sessionMark, string $createdAt = 'now'): string
    {
        $claims = [
            'sessionMark' => $sessionMark,
        ];

        $Token = new Token(
            aud: [ServerEnv::SERVER_NAME()],
            createdAt: $createdAt,
            expireDuration: '+1 month',
            claims: $claims
        );
        return $Token->jwt;
    }

    /**
     * @throws AccessErr
     * @throws AuthErr
     */
    public static function validation(string $jwt): void
    {
        Token::validation(jwt: $jwt);
    }

    public static function marker(string $jwt): string
    {
        return Token::toArray($jwt)['sessionMark'];
    }
}