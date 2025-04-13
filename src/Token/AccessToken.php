<?php

namespace Symphograph\Bicycle\Token;


use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;

class AccessToken
{

    /**
     * @throws AuthErr
     */
    public static function create(
        string $sessionMark,
        int    $uid,
        int    $accountId,
        array  $powers,
        string $createdAt = 'now',
        string $authType = 'default',
    ): string
    {
        $audience = Env::getJWT()->audience;

        $audience[] = ServerEnv::SERVER_NAME();

        $claims = [
            'accountId'   => $accountId,
            'sessionMark' => $sessionMark,
            'powers'      => $powers,
            'authType'    => $authType,
        ];

        $Token = new Token(
            uid: $uid,
            aud: $audience,
            createdAt: $createdAt,
            expireDuration: '+1 day',
            claims: $claims
        );
        return $Token->jwt;
    }

    /**
     * @throws AuthErr
     * @throws AccessErr
     */
    public static function validation(string $jwt, $needPowers = [], bool $ignoreExpire = false): void
    {
        Token::validation(
            jwt: $jwt,
            ignoreExpire: $ignoreExpire
        );

        self::checkPowers($jwt, $needPowers);
    }

    /**
     * @throws AccessErr
     */
    private static function checkPowers(string $jwt, array $needPowers): void
    {
        if (empty($needPowers)) return;
        $tokenPowers = self::powers($jwt);
        $intersect = array_intersect($needPowers, $tokenPowers);
        if (!count($intersect)) throw new AccessErr('Token has not required Powers');
    }

    private static function powers(string $jwt): array
    {
        return Token::toArray($jwt)['powers'] ?? [];
    }

    public static function userId(string $jwt): int
    {
        return Token::toArray($jwt)['uid'];
    }

    public static function accountId(string $jwt): int
    {
        return Token::toArray($jwt)['accountId'];
    }

    public static function byHTTP(): string
    {
        return $_SERVER['HTTP_ACCESSTOKEN']
            ?? throw new AuthErr('Unauthorized');
    }


    public static function sessionMark(string $jwt): string
    {
        return Token::toArray($jwt)['sessionMark'];
    }

}