<?php

namespace Symphograph\Bicycle\Auth\Account\Profile\Discord;

use Symphograph\Bicycle\Env\Env;
use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Errors\CurlErr;
use Symphograph\Bicycle\JsonDecoder;

class DiscordApi
{
    const string authorizeURL = 'https://discord.com/api/oauth2/authorize';
    const string tokenURL     = 'https://discord.com/api/oauth2/token';
    const string apiURLBase   = 'https://discord.com/api/users/@me';
    const string revokeURL    = 'https://discord.com/api/oauth2/token/revoke';


    #[NoReturn] public static function login(string $state): void
    {
        $params = [
            'client_id'     => Env::getDiscordSecrets()->clientId,
            'redirect_uri'  => self::callBackUrl(),
            'response_type' => 'code',
            'scope'         => 'identify guilds email',
            'state'         => $state
        ];

        // Redirect the user to Discord's authorization page
        header('Location: ' . self::authorizeURL . '?' . http_build_query($params));
        die();
    }

    /**
     * @throws CurlErr
     */
    public static function getUser(string $code): DiscordUser
    {
        $token = self::getToken($code);
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

    private static function getToken(string $code): string
    {
        // Exchange the auth code for a token
        $secrets = Env::getDiscordSecrets();
        $response = self::apiRequest(self::tokenURL, [
            'grant_type'    => 'authorization_code',
            'client_id'     => $secrets->clientId,
            'client_secret' => $secrets->clientSecret,
            'redirect_uri'  => self::callBackUrl(),
            'code'          => $code
        ]);

        return $response->access_token;
    }

    private static function apiRequest($url, $fields = [], $headers = [])
    {
        $options = [
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_RETURNTRANSFER => true,
        ];

        if (!empty($fields)) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($fields);
        }

        $headers[] = 'Accept: application/json';

        $options[CURLOPT_HTTPHEADER] = $headers;


        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        if(!$response) throw new CurlErr();
        $response = json_decode($response);
        if(!empty($response->error)) {
            $descr = $response->error_description ?? '';
            $msg = $response->error . $descr;
            throw new CurlErr(trim($msg));
        }
        return $response;
    }

    private static function callBackUrl(): string
    {
        $fold = match (true) {
            str_starts_with($_SERVER['DOCUMENT_URI'], '/tauth/') => 'tauth',
            str_starts_with($_SERVER['DOCUMENT_URI'], '/auth/') => 'auth',
            str_starts_with($_SERVER['DOCUMENT_URI'], '/tapi/') => 'tauth',
            str_starts_with($_SERVER['DOCUMENT_URI'], '/api/') => 'auth',
            default => throw new AppErr('Invalid URL: ' . $_SERVER['DOCUMENT_URI']),
        };
        $serverName = ServerEnv::SERVER_NAME();
        return "https://$serverName/$fold/login/discord/callback.php";
    }

    #[NoReturn] public static function sendCodeToPopup(string $url, $code): void
    {
        $json = json_encode([
            'status' => 'success',
            'code' => $code
        ]);
        echo <<<HTML
    <script>
      window.opener.postMessage({
        type: 'discord-auth-response',
        payload: $json
      }, '$url')
      
      window.close();
    </script>
    HTML;
        exit;
    }
}