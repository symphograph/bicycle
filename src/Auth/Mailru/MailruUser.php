<?php

namespace Symphograph\Bicycle\Auth\Mailru;


use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\DTO\SocialAccountDTO;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\PDO\DB;

class MailruUser extends SocialAccountDTO
{
    use DTOTrait;
    const string tableName = 'user_mailru';
    public ?int    $id;
    public ?int    $user_id;
    public ?string $client_id;
    public ?string $gender;
    public ?string $name;
    public ?string $nickname;
    public ?string $first_name;
    public ?string $last_name;
    public ?string $locale;
    public ?string $email;
    public ?string $birthday;
    public ?string $image;

    public static function byEmail(string $email): self|bool
    {
        $qwe = DB::qwe("select * from user_mailru where email = :email", ['email'=>$email]);
        return $qwe->fetchObject(self::class);
    }

    public static function byContact(string $contactValue): self|false
    {
        return self::byEmail($contactValue);
    }

    public static function byMailruToken(string $token, $userUrl): self
    {
        $url = $userUrl . '?access_token=' . $token;
        $user = file_get_contents($url)
            or throw new AuthErr('getUser Err');

        $MailUser = new self();
        $user = json_decode($user);
        if(!is_object($user))
            throw new AuthErr('$user is not object');

        $MailUser->bindSelf($user);
        return $MailUser;
    }

    public function getNickName(): string
    {
        if($this->nickname){
            return $this->nickname;
        }

        return trim(($this->first_time ?? '') . ' ' . ($this->last_name ?? ''));
    }

    private function beforePut(): void
    {
        if(isset($this->birthday)){
            $this->birthday = date('Y-m-d', strtotime($this->birthday));
        }
    }

}