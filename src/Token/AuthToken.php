<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Env\Server\ServerEnv;


class AuthToken
{
    public static function create(int $minBeforeExpired, string $hash = ''): string
    {
        $claims = [
            'origin' => ServerEnv::HTTP_ORIGIN(),
            'state' => bin2hex(random_bytes(16)),
            'hash' => $hash,
        ];
        $Token = new Token(
            aud: [ServerEnv::SERVER_NAME()],
            createdAt: 'now',
            expireDuration: "+$minBeforeExpired min",
            claims: $claims,
        );
        return $Token->jwt;
    }

    public static function origin(string $jwt): string
    {
        return Token::toArray($jwt)['origin'];
    }

    public static function state(string $jwt): string
    {
        return Token::toArray($jwt)['state'];
    }

    public static function hash(string $jwt): string
    {
        return Token::toArray($jwt)['hash'];
    }
}