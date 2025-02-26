<?php

namespace Symphograph\Bicycle\Api\VK;

use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\Env\Env;
use VK\OAuth\Scopes\VKOAuthUserScope;
use VK\OAuth\VKOAuth;
use VK\OAuth\VKOAuthDisplay;
use VK\OAuth\VKOAuthResponseType;

class VKAuth
{

    #[NoReturn] public static function getCode(): void
    {
        $vk = Env::getVKSecrets();
        $oauth = new VKOAuth();

        $client_id = $vk->getAppId();
        $redirect_uri = "https://$_SERVER[SERVER_NAME]/$vk->codeRedirect";
        $display = VKOAuthDisplay::PAGE;
        $scope = array(VKOAuthUserScope::VIDEO, VKOAuthUserScope::GROUPS, VKOAuthUserScope::OFFLINE);
        $state = 'secret_state_code';

        $browser_url = $oauth->getAuthorizeUrl(VKOAuthResponseType::CODE, $client_id, $redirect_uri, $display, $scope, $state);
        header("Location: $browser_url");
        exit;
    }

    public static function getTokenByCode(string $code): ?array
    {
        $url = 'https://oauth.vk.com/access_token';
        $vk = Env::getVKSecrets();
        $params = [
            'client_id' => $vk->appId,
            'client_secret' => $vk->privateKey,
            'redirect_uri' => "https://$_SERVER[SERVER_NAME]/$vk->codeRedirect",
            'code' => $code
        ];

        $query = http_build_query($params);
        $response = file_get_contents("$url?$query");
        if (empty($response)) return null;

        return json_decode($response, true);
    }
}