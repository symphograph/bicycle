<?php

namespace Symphograph\Bicycle\Auth\Account;


use Symphograph\Bicycle\Auth\Account\Profile\Discord\DiscordUser;
use Symphograph\Bicycle\Auth\Account\Profile\Email\EmailUserDTO;
use Symphograph\Bicycle\Auth\Account\Profile\Mailru\MailruUser;
use Symphograph\Bicycle\Auth\Account\Profile\Telegram\TeleUser;
use Symphograph\Bicycle\Auth\Account\Profile\Vkontakte\VkUser;
use Symphograph\Bicycle\Auth\Account\Profile\Yandex\YandexProfileDTO;
use Symphograph\Bicycle\DTO\ModelTrait;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\Account\AccountNoExistsErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\Helpers\Date;
use Symphograph\Bicycle\PDO\DB;
use Symphograph\Bicycle\Token\AccessToken;

class Account extends AccountDTO
{
    use ModelTrait;

    public ?string       $externalAvaUrl;
    public ?string       $nickName;
    // public ?Avatar       $Avatar;
    // public ?TeleUser     $TeleUser;
    //public ?MailruUser   $MailruUser;
    //public ?DiscordUser  $DiscordUser;
    //public ?VkUser       $VkUser;
    //public AccProfileDTO $socialProfile;
    //public string        $contactValue;


    public static function create(AccountType $authType, int $userId): self
    {
        $Account = new self();
        $Account->userId = $userId;
        $Account->authType = $authType->value;
        $datetime = date('Y-m-d H:i:s');
        $Account->createdAt = $datetime;
        $Account->visitedAt = $datetime;
        return $Account;
    }

    public function initData(): static
    {
        $this->initProfileValues();
        // $this->datesToISO_8601();
        return $this;
    }

    private function datesToISO_8601(): void
    {
        $this->createdAt = Date::dateFormatFeel($this->createdAt, 'c');
        $this->visitedAt = Date::dateFormatFeel($this->visitedAt, 'c');
    }

    /**
     * @throws AccessErr
     * @throws AuthErr
     */
    public static function byJwt(string $jwt): ?self
    {
        $accountId = AccessToken::byJwt($jwt,[])->accountId;
        return Account::byId($accountId);
    }

    public function initProfileValues(): static
    {
        match ($this->authType) {
            AccountType::Default->value => $this->initDefault(),
            AccountType::Telegram->value => $this->initTeleUser(),
            AccountType::Mailru->value => $this->initMailruUser(),
            AccountType::Discord->value => $this->initDiscordUser(),
            AccountType::VKontakte->value => $this->initVkUser(),
            AccountType::Yandex->value => $this->initYandexUser(),
            AccountType::Email->value => $this->initEmailUser(),
            default => null
        };
        return $this;
    }

    private function initDefault(): void
    {
        $this->nickName = 'Не авторизован';
    }

    private function initTeleUser(): void
    {
        $profile = TeleUser::byAccountId($this->id)
            ?? throw new AccountNoExistsErr($this->id);

        $this->externalAvaUrl = $profile->externalAvaUrl();
        $this->nickName = $profile->nickName();
        //$this->contactValue = $profile->username;
    }

    private function initYandexUser(): void
    {
        $profile = YandexProfileDTO::byAccountId($this->id)
            ?? throw new AccountNoExistsErr($this->id);

        $this->externalAvaUrl = "https://avatars.yandex.net/get-yapic/$profile->default_avatar_id/islands-50";
        $this->nickName = $profile->nickName();
        //$this->contactValue = $profile->default_email;
    }

    private function initMailruUser(): void
    {
        $profile = MailruUser::byAccountId($this->id)
        or throw new AccountNoExistsErr($this->id);

        $this->externalAvaUrl = $profile->externalAvaUrl();
        $this->nickName = $profile->nickName();
        //$this->contactValue = $profile->email;
    }

    private function initDiscordUser(): void
    {
        $profile = DiscordUser::byAccountId($this->id)
        or throw new AccountNoExistsErr($this->id);

        $this->externalAvaUrl = $profile->externalAvaUrl();
        $this->nickName = $profile->nickName();
        //$this->contactValue = $profile->username;
    }

    private function initVkUser(): void
    {
        $profile = VkUser::byAccountId($this->id)
        or throw new AccountNoExistsErr($this->id);

        $this->externalAvaUrl = $profile->externalAvaUrl();
        $this->nickName = $profile->nickName();
        //$this->contactValue = $profile->domain;
    }

    private function initEmailUser(): void
    {
        $profile = EmailUserDTO::byAccountId($this->id)
        or throw new AccountNoExistsErr($this->id);
        $this->nickName = $profile->nickName();
    }

    public function isDefault(): bool
    {
        return $this->authType === AccountType::Default->value;
    }

    public function unlinkFromUser(): void
    {
        $sql = "update accounts set userId = null where id = :id";
        $params = ['id' => $this->id];
        DB::qwe($sql, $params);
    }

    public function authType(): AccountType
    {
        return AccountType::from($this->authType);
    }

}