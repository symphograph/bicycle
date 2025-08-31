<?php

namespace Symphograph\Bicycle\Auth\Account\Profile;

use Symphograph\Bicycle\Auth\Account\AccountType;
use Symphograph\Bicycle\Auth\Account\Profile\Discord\DiscordUser;
use Symphograph\Bicycle\Auth\Account\Profile\Email\EmailUserDTO;
use Symphograph\Bicycle\Auth\Account\Profile\Mailru\MailruUser;
use Symphograph\Bicycle\Auth\Account\Profile\Telegram\TeleUser;
use Symphograph\Bicycle\Auth\Account\Profile\Vkontakte\VkUser;
use Symphograph\Bicycle\Auth\Account\Profile\Yandex\YandexProfileDTO;

abstract class AccProfileDTO implements AccProfileITF
{
    public int $accountId;

    public function setAccountId(int $accountId): void
    {
        $this->accountId = $accountId;
    }

    protected function nickByNames(): string
    {
        $nickName = ($this->first_name ?? '') . ' ' . ($this->last_name ?? '');
        return trim($nickName);
    }

    public function getAuthType(): AccountType
    {
        return match (true) {
            $this instanceof DiscordUser => AccountType::Discord,
            $this instanceof EmailUserDTO => AccountType::Email,
            $this instanceof MailruUser => AccountType::Mailru,
            $this instanceof TeleUser => AccountType::Telegram,
            $this instanceof VkUser => AccountType::VKontakte,
            $this instanceof YandexProfileDTO => AccountType::Yandex,
            default => AccountType::Default
        };
    }

}