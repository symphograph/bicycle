<?php

namespace Symphograph\Bicycle\Auth;

use Symphograph\Bicycle\Auth\Account\Account;
use Symphograph\Bicycle\Auth\Account\AccountList;
use Symphograph\Bicycle\Auth\Account\AccountManager;
use Symphograph\Bicycle\Auth\Account\AccountType;
use Symphograph\Bicycle\Auth\Account\Profile\AccProfileITF;
use Symphograph\Bicycle\Auth\Account\Profile\Discord\DiscordUser;
use Symphograph\Bicycle\Auth\Account\Profile\Email\EmailUserDTO;
use Symphograph\Bicycle\Auth\Account\Profile\Telegram\TeleUser;
use Symphograph\Bicycle\Auth\Account\Profile\Vkontakte\VkUser;
use Symphograph\Bicycle\Auth\Account\Profile\Yandex\YandexProfileDTO;
use Symphograph\Bicycle\Auth\Device\Device;
use Symphograph\Bicycle\Auth\Session\Session;
use Symphograph\Bicycle\Auth\User\User;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\Token\AccessToken;
use Symphograph\Bicycle\Token\SessionToken;
use Symphograph\Bicycle\Token\Token;

class OAuthManager
{
    public Account $account;
    public User $user;

    public function __construct(
        public Session $sess,
        public Device $device,
    ){}

    public function setAccount(Account $account): static
    {
        $this->account = $account;
        return $this;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function loginOrRegister(AccountType $authType, AccProfileITF $responseProfile): void
    {
        $sess = $this->sess;

        $existingProfile = self::getExistingProfile($authType, $responseProfile);

        if(empty($existingProfile)) {
            $this->user = User::byAccount($sess->accountId) // Берем юзера с прошлой сессии.
                ?? throw new AuthErr('user does not exist');
            $this->account = AccountManager::create($authType, $this->user->id, $responseProfile)->account;
        }else{
            $this->account = Account::byId($existingProfile->accountId);
            $this->user = User::byAccount($this->account->id);
        }

        $responseProfile->setAccountId($this->account->id);
        $responseProfile->putToDB();
        $this->updateData();
    }

    public function updateData(): void
    {
        $sess = $this->sess;
        $device = $this->device;
        $account = $this->account;
        $user = $this->user;

        $datetime = date('Y-m-d H:i:s');

        $sess->accountId = $account->id;
        $sess->lastIp = ServerEnv::REMOTE_ADDR();
        $sess->visitedAt = $datetime;
        $sess->putToDB();

        $account->visitedAt = $datetime;
        $account->putToDB();

        $user->visitedAt = $datetime;
        $user->putToDB();

        $device->linkToUser($user->id);
        $device->update();
    }

    private static function getExistingProfile(
        AccountType $authType,
        AccProfileITF $responseProfile
    ): ?AccProfileITF
    {
        return match ($authType) {
            AccountType::Telegram => TeleUser::byId($responseProfile->id),
            AccountType::VKontakte => VkUser::byId($responseProfile->uid),
            AccountType::Discord => DiscordUser::byId($responseProfile->id),
            AccountType::Email => EmailUserDTO::byEmail($responseProfile->email),
            AccountType::Yandex => YandexProfileDTO::byEmail($responseProfile->default_email),
            default => null
        };
    }

    public function getAccessToken(): string
    {
        $sess = $this->sess;
        $account = $this->account;

        return AccessToken::create(
            sessionMark: $sess->marker,
            uid: $account->userId ?? 0,
            accountId: $account->id,
            powers: [],
            createdAt: $sess->visitedAt,
            authType: $account->authType,
        );
    }

    public function getSessionToken(): string
    {
        $sess = $this->sess;
        return SessionToken::create($sess->marker, $sess->visitedAt);
    }

    public function getTokensForResponse(): array
    {
        $SessionToken = $this->getSessionToken();
        $AccessToken = $this->getAccessToken();

        $data = [
            'SessionToken' => $SessionToken,
            'AccessToken'  => $AccessToken
        ];
        $data['curAccount'] = $this->account->initData();

        if(Env::isDebugMode()){
            $data['Session'] = $this->sess;
            $data['Device'] = $this->device;
            $data['accessTokenData'] = Token::toArray($AccessToken);
            $data['sessionTokenData'] = Token::toArray($SessionToken);
            $data['accounts'] = AccountList::byDevice($this->device->id)
                ->excludeDefaults()
                ->initData()
                ->getList();

        }
        return $data;
    }


}