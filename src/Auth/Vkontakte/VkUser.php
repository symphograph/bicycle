<?php

namespace Symphograph\Bicycle\Auth\Vkontakte;


use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\DTO\SocialAccountDTO;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\PDO\DB;

class VkUser extends SocialAccountDTO
{
    use DTOTrait;
    const string colId     = 'uid';
    const string tableName = 'user_vkontakte';

    public int    $uid;
    public string $domain;
    public string $first_name;
    public string $last_name;
    public string $photo;
    public string $photo_rec;

    public static function byGet(): self
    {
        if (empty($_GET)) {
            throw new AuthErr('VkData is empty');
        }
        $vkUser = self::byArray($_GET);
        $vkUser->checkHash($_GET['hash']);
        return $vkUser;
    }

    private static function byArray(array|object $arr): self
    {
        $VkUser = new self();
        $arr = (object)$arr;
        $vars = get_class_vars(self::class);
        foreach ($vars as $k => $v) {
            if ($k === 'accountId') continue;
            if ($k === 'domain') continue;
            if (!isset($arr->$k)) {
                throw new AuthErr('VkData is invalid');
            }
            $VkUser->$k = $arr->$k;
        }
        return $VkUser;
    }

    private function checkHash(string $hash): void
    {
        if (empty($hash)) {
            throw new AuthErr('Empty vkHash');
        }
        $secrets = Env::getVKSecrets();
        ($hash === md5($secrets->appId . $this->uid . $secrets->privateKey))
        or throw new AuthErr('Invalid vkHash');
    }

    public static function byContact(string $contactValue): self|false
    {
        $qwe = DB::qwe("
            select * from user_vkontakte 
            where domain = :contactValue",
            ['contactValue' => $contactValue]
        );
        return $qwe->fetchObject(self::class);
    }

}