<?php

namespace Symphograph\Bicycle\Auth\Account\Profile\Mailru;




use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\Api\Api;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\Auth\AuthErr;

class OAuthMailRu
{
    const string authUrl  = 'https://oauth.mail.ru/login';
    const string tokenUrl = 'https://oauth.mail.ru/token';
    const string userUrl  = 'https://oauth.mail.ru/userinfo';
    const string callback = '/tauth/login/mailru/callback.php';

    private static string    $token;
    public static string|int $user_id;
    public static mixed      $userData;

    #[NoReturn] public static function goToAuth($secret): void
    {
        $url = self::authUrl .
            '?client_id=' . $secret->app_id .
            '&response_type=code' .
            '&redirect_uri=' . urlencode(self::callbackUrl()) .
            '&state=' . '12345';

        self::redirect($url);
    }

    public static function getToken($code, $secret): bool
    {
        $data = [
            'client_id'     => $secret->app_id,
            'client_secret' => $secret->app_secret,
            'grant_type'    => 'authorization_code',
            'code'          => trim($code),
            'redirect_uri'  => self::callbackUrl()
        ];

        $response = Api::curl(self::tokenUrl, $data);

        $result = json_decode($response);
        if (empty($result)) {
            throw new AuthErr();
        }
        //printr($result);
        self::$token = $result->access_token;
        return true;
    }

    public static function getUser(): MailruUser
    {
        return MailruUser::byMailruToken(self::$token, self::userUrl);
    }

    private static function callbackUrl(): string
    {
        return 'https://' . ServerEnv::SERVER_NAME() . self::callback;
    }

    #[NoReturn] private static function redirect($uri = ''): void
    {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: ".$uri);
        exit;
    }

}