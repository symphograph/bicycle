<?php

namespace Symphograph\Bicycle\Auth\Discord;

use App\Api;
use App\AuthCallBack;
use Symphograph\Bicycle\Auth\Discord\DiscordUser;
use Symphograph\Bicycle\Env\Config;
use Symphograph\Bicycle\Env\Env;
use App\User\Sess;
use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\HTTP\Request;
use Symphograph\Bicycle\JsonDecoder;

class DiscordApi
{
    const authorizeURL = 'https://discord.com/api/oauth2/authorize';
    const tokenURL = 'https://discord.com/api/oauth2/token';
    const apiURLBase = 'https://discord.com/api/users/@me';
    const revokeURL = 'https://discord.com/api/oauth2/token/revoke';


    #[NoReturn] public static function login(): void
    {
        $state = bin2hex(random_bytes(12));
        setcookie('discordState', $state,
            Config::cookOpts(expires: time() + 600, path: '/auth/', samesite: 'None')
        );
        $params = [
            'client_id'     => Env::getDiscordSecrets()->clientId,
            'redirect_uri'  => self::getRedirectUrl(),
            'response_type' => 'code',
            'scope'         => 'identify guilds',
            'state'         => $state
        ];

        // Redirect the user to Discord's authorization page
        header('Location: ' . self::authorizeURL . '?' . http_build_query($params));
        die();
    }

    public static function getUser(): DiscordUser
    {
        $token = self::getToken();
        $user = self::apiRequest(
            url: self::apiURLBase,
            headers: ['Authorization: Bearer ' . $token]
        );
        // printr($user);
        /** @var DiscordUser $DiscordUser */
        $DiscordUser = JsonDecoder::cloneFromAny($user, DiscordUser::class);
        // printr($DiscordUser);
        return $DiscordUser;
    }

    private static function getToken(): string
    {
        // Exchange the auth code for a token
        session_start();
        $secrets = Env::getDiscordSecrets();
        $token = self::apiRequest(self::tokenURL, [
            'grant_type'    => 'authorization_code',
            'client_id'     => $secrets->clientId,
            'client_secret' => $secrets->clientSecret,
            'redirect_uri'  => self::getRedirectUrl(),
            'code'          => Request::get('code')
        ]);
        return $token->access_token;
    }

    public static function apiRequest($url, $post=FALSE, $headers=[]) {
        $options = [
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_RETURNTRANSFER => true,
        ];

        if($post) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($post);
        }

        //$headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'Accept: application/json';
        if(Request::session('access_token'))
            $headers[] = 'Authorization: Bearer ' . Request::session('access_token');
        $options[CURLOPT_HTTPHEADER] = $headers;


        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        return json_decode($response);
    }

    private static function getRedirectUrl(): string
    {
        $serverName = ServerEnv::SERVER_NAME();
        return "https://$serverName/auth/discord/callback.php";
    }
}