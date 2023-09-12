<?php
namespace Symphograph\Bicycle\Auth\Telegram;
use Symphograph\Bicycle\DB;
use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\DTO\ModelTrait;
use Symphograph\Bicycle\DTO\SocialAccountDTO;

class TeleUser extends TeleUserDTO
{
    use ModelTrait;

    public string $hash       = '';


    public static function byData(array|object $auth_data) : self
    {
        $TeleUser = new TeleUser();
        $TeleUser->bindSelf($auth_data);
        return $TeleUser;
    }

    public function putToDB(): void
    {
        $this->auth_date = date('Y-m-d H:i:s',$this->auth_date);
        $parentObject = new parent();
        $parentObject->bindSelf($this);
        $parentObject->putToDB();
    }

}