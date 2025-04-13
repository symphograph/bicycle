<?php

namespace Symphograph\Bicycle\Auth\Account\Profile\Yandex;

use Symphograph\Bicycle\Auth\Account\Profile\AccProfileDTO;
use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\PDO\DB;

class YandexProfileDTO extends AccProfileDTO
{
    use DTOTrait;

    const string tableName = 'user_yandex';

    public int     $id;
    public string  $first_name;
    public string  $last_name;
    public ?string  $display_name;
    public string  $default_email;
    public string  $real_name;
    public bool    $is_avatar_empty;
    public ?string $birthday;
    public string  $default_avatar_id;
    public string  $login;
    public ?string $sex;
    public string  $client_id;
    public string  $psuid;


    public static function byContact(string $contactValue): ?static
    {
        $sql = "SELECT * FROM user_yandex WHERE default_email = :email";
        $params = ['email' => $contactValue];
        return DB::qwe($sql, $params)->fetchObject(static::class) ?: null;
    }

    public static function byEmail(string $email): ?static
    {
        return static::byContact($email);
    }

    public function externalAvaUrl(): string
    {
        return "https://avatars.yandex.net/get-yapic/$this->default_avatar_id/islands-50";
    }

    public function nickName(): string
    {
        return $this->display_name ?? static::nickByNames();
    }

    public static function byAuthProvider(string $token): YandexProfileDTO
    {
        // Запрос к API Yandex для проверки токена
        $ch = curl_init('https://login.yandex.ru/info');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) throw new AppErr('Network Err');

        $userData = json_decode($response, true);

        return YandexProfileDTO::byBind($userData);
    }
}