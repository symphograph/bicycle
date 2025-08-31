<?php

namespace Symphograph\Bicycle\Auth;

use Symphograph\Bicycle\Auth\Session\{Session};
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Env\Services\Client;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\Errors\Auth\EmptyOriginErr;
use Symphograph\Bicycle\Errors\Auth\InvalidOriginErr;
use Symphograph\Bicycle\HTTP\Cookie;
use Symphograph\Bicycle\Token\{AccessToken, SessionToken};

class CallBack
{
    /**
     * @param string $refUrl
     * @return void
     * @throws AuthErr
     */
    public static function checkReferer(string $refUrl): void
    {
        (ServerEnv::HTTP_REFERER() ?? '') === $refUrl
            or throw new AuthErr('unknown referer');
    }

    /**
     * @return void
     * @throws AuthErr
     * @throws EmptyOriginErr
     * @throws InvalidOriginErr
     */
    public static function checkOriginCookie(): void
    {
        if(empty($_COOKIE['origin'])) {
            throw new AuthErr('origin is missed');
        }
        Client::byOrigin($_COOKIE['origin']);
    }

    public static function setCookies(): void
    {
        $opts = Cookie::opts(600, '/', 'Lax');
        Cookie::set('origin', ServerEnv::HTTP_ORIGIN(), $opts);
        Cookie::set(Session::cookieName, SessionToken::marker($_POST['SessionToken']), $opts);
    }

    /**
     * @return void
     * @throws AuthErr
     * @throws EmptyOriginErr
     * @throws InvalidOriginErr|AccessErr
     */
    public static function loginChecks(): void
    {
        $origin = ServerEnv::HTTP_ORIGIN();
        if(empty($origin)) throw new EmptyOriginErr();

        Client::byOrigin($origin);

        AccessToken::byJwt($_POST['AccessToken'], []);
        SessionToken::validation($_POST['SessionToken']);

        $marker = SessionToken::marker($_POST['SessionToken']);
        Session::byMarker($marker)
            ?? throw new AuthErr('Session does not exist', 'Попробуйте еще раз');
        self::setCookies();
    }

    public static function redirectToFrontend(): void
    {
        $url = $_COOKIE['origin'];
        header("Location: $url/auth/callback");
    }

}