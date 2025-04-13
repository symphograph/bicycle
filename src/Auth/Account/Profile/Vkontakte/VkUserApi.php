<?php

namespace Symphograph\Bicycle\Auth\Account\Profile\Vkontakte;

use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Token\AuthToken;
use VK\Client\VKApiClient;

class VkUserApi extends VkUser
{
    public static function byVkApi(int|string $contactValue): ?VkUser
    {
        $vk = new VKApiClient();
        $access_token = Env::getVKSecrets()->serviceKey;
        $response = $vk->users()->get($access_token, [
            'user_ids' => [$contactValue],
            'fields'   => ['domain', 'photo_100', 'photo_rec', 'photo'],
        ]);
        if (empty($response[0])) {
            return null;
        }

        $vkUser = VkUser::byBind($response[0]);

        $vkUser->uid = $response[0]['id'];
        $vkUser->domain = $response[0]['domain'];
        $vkUser->photo_rec = $response[0]['photo_100'];
        return $vkUser;
    }

    #[NoReturn] public static function sendUidToPopup(string $origin, VkUser $profile): void
    {
        $json = json_encode([
            'status' => 'success',
            'profile'    => $profile,
            'token' => AuthToken::create(10, $profile->knownHash()),
        ]);
        echo <<<HTML
            <script>
              window.opener.postMessage({
                type: 'vkontakte-auth-response',
                payload: $json
              }, '$origin')
              
              window.close();
            </script>
        HTML;
        exit();
    }
}