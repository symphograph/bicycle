<?php

namespace Symphograph\Bicycle\Auth\Account\Profile\Email;

use Symphograph\Bicycle\Auth\Account\Profile\AccProfileDTO;
use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\PDO\DB;

class EmailUserDTO extends AccProfileDTO
{
    use DTOTrait;

    const string colId = 'accountId';
    const string tableName = 'user_email';

    public string $email;
    public ?string $confirmedAt;

    public static function byEmail(string $email): ?self
    {
        $sql = "SELECT * FROM user_email WHERE email = :email";
        $params = ['email' => $email];
        return DB::qwe($sql, $params)?->fetchObject(self::class) ?: null;
    }

    public static function byContact(string $contactValue): ?self
    {
        return self::byEmail($contactValue);
    }

    public static function create(string $email): self
    {
        $obj = new self();
        $obj->email = $email;
        $obj->confirmedAt = date('Y-m-d H:i:s');
        return $obj;
    }


    public function externalAvaUrl(): string
    {
        return '';
    }

    public function nickName(): string
    {
        return explode('@', $this->email)[0];
    }
}