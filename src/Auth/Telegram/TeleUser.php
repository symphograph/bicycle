<?php
namespace Symphograph\Bicycle\Auth\Telegram;
use Symphograph\Bicycle\DTO\ModelTrait;

class TeleUser extends TeleUserDTO
{
    use ModelTrait;

    public string $hash       = '';


    public static function byUserName(string $username): self|false
    {
        $parent = parent::byUserName($username);
        if(!$parent){
            return false;
        }
        return self::byBind($parent);
    }

    public function initData(): void
    {
        $Telegram = new Telegram();
        $this->hash = $Telegram->getHash($this->getAllProps());
    }

}