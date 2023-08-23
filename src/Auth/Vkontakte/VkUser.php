<?php

namespace Symphograph\Bicycle\Auth\Vkontakte;


use Symphograph\Bicycle\DB;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\AuthErr;

class VkUser
{
    public int    $uid;
    public int    $accountId;
    public string $first_name;
    public string $last_name;
    public string $photo;
    public string $photo_rec;

    public static function byId(int $uid): self|false
    {
        $qwe = qwe("select * from user_vk where uid = :uid", ['uid'=>$uid]);
        return $qwe->fetchObject(self::class);
    }

    public static function byGet(): self
    {
        if (empty($_GET)) {
            throw new AuthErr('VkData is empty');
        }
        $vkUser = self::byArray($_GET);
        $vkUser->checkHash($_GET['hash']);
        return $vkUser;
    }

    public static function byArray(array|object $arr): self
    {
        $VkUser = new self();
        $arr = (object)$arr;
        $vars = get_class_vars(self::class);
        foreach ($vars as $k => $v) {
            if($k === 'accountId') continue;
            if (!isset($arr->$k)) {
                throw new AuthErr('VkData is invalid');
            }
            $VkUser->$k = $arr->$k;
        }
        return $VkUser;
    }

    public function checkHash(string $hash)
    {
        if(empty($hash)){
            throw new AuthErr('Empty vkHash');
        }
        $secrets = Env::getVKSecrets();
        ($hash === md5($secrets->appId . $this->uid . $secrets->privateKey))
            or throw new AuthErr('Invalid vkHash');
    }

    public function putToDB(): void
    {
        $params = DB::initParams($this);
        DB::replace('user_vk', $params);
    }
}