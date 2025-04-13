<?php
namespace Symphograph\Bicycle\Auth\Account\Profile\Telegram;

use Symphograph\Bicycle\DTO\ModelTrait;

class TeleUser extends TeleUserDTO
{
    use ModelTrait;

    public string $hash       = '';

    public function initData(): void
    {
        $this->hash = Telegram::getHash($this->getAllProps());
    }

}