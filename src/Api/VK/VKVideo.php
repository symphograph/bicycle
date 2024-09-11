<?php

namespace Symphograph\Bicycle\Api\VK;

use Symphograph\Bicycle\Env\Env;

class VKVideo
{
    public static function getList(int $groupId): array
    {
        $vk = Env::getVKSecrets();
        $accessToken = $vk->longToken;
        $version = '5.199';
        $videos = [];
        $count = 100; // Максимальное количество видео на один запрос
        $offset = 0;

        do {
            $params = [
                'owner_id' => "-$groupId",
                'access_token' => $accessToken,
                'v' => $version,
                'count' => $count,
                'offset' => $offset
            ];

            $url = "https://api.vk.com/method/video.get?" . http_build_query($params);
            $page = curl($url);
            $data = json_decode($page, true);

            if (isset($data['response']['items'])) {
                $videos = array_merge($videos, $data['response']['items']);
                $offset += $count;
                $total = $data['response']['count'];
            } else {
                break;
            }
        } while ($offset < $total);

        return $videos;
    }
}