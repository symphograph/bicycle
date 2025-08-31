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
use Symphograph\Bicycle\Token\MergeUsersToken;
use Symphograph\Bicycle\Token\SessionToken;
use Symphograph\Bicycle\Token\Token;

class OAuthManager
{
    public Account $account;
    public User     $user;
    public bool     $isNewLinkToUser = false;
    private ?string $mergeToken;

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

    public function loginOrRegister(AccProfileITF $responseProfile): void
    {
        $authType = $responseProfile->getAuthType();
        $existingProfile = self::getExistingProfile($authType, $responseProfile);

        if (!empty($existingProfile)) {
            $this->handleLogin($existingProfile, $responseProfile);
        } else {
            $this->handleRegister($authType, $responseProfile);
        }

        $responseProfile->setAccountId($this->account->id);
        $responseProfile->putToDB();
        $this->updateData();
    }


    private function handleLogin(AccProfileITF $existingProfile): void
    {
        $this->account = Account::byId($existingProfile->accountId);

        if (!empty($this->account->userId)) {
            $this->user = User::byAccount($this->account->id);
        } else {
            $this->user = User::byAccount($this->sess->accountId);
            $this->account->userId = $this->user->id;
            //$this->isNewLinkToUser = true;
        }

        $this->checkForMerge();
    }

    private function checkForMerge(): void
    {
        $prevAccount = Account::byId($this->sess->accountId);
        if ($prevAccount->isDefault()) return;

        $prevUser = User::byAccount($this->sess->accountId);

        if ($prevUser->id !== $this->user->id) {
            $fromUser = $this->user;
            $this->user = $prevUser;
            $this->account->userId = $this->user->id;

            $this->mergeToken = MergeUsersToken::create($fromUser->id, $this->user->id)->jwt;
        }
    }

    private function handleRegister(AccountType $authType, AccProfileITF $responseProfile): void
    {
        $this->user = User::byAccount($this->sess->accountId);
        $this->account = AccountManager::create($authType, $this->user->id, $responseProfile)->account;

        if(!$this->account->isDefault()){
            $this->isNewLinkToUser = true;
        }
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
        if(empty($user->publicNick) && !$account->isDefault()) {
            $user->publicNick = $account->initData()->nickName;
        }
        $user->putToDB();

        $device->linkToUser($user->id);
        $device->linkToAccount($account->id);
        $device->update();
        $device->setCookie();
        $sess->setCookie();

        if(!$account->isDefault()) {
            $user->delDefaultAccounts();
        }
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

    /**
     * @param int[] $powers
     * @return AccessToken
     * @throws AuthErr
     */
    private function getAccessToken(array $powers): AccessToken
    {
        $sess = $this->sess;
        $account = $this->account;

        return AccessToken::create(
            sessionMark: $sess->marker,
            userId: $account->userId,
            accountId: $account->id,
            powers: $powers,
            createdAt: $sess->visitedAt,
            authType: AccountType::from($account->authType),
        );
    }

    private function getSessionToken(): string
    {
        $sess = $this->sess;
        return SessionToken::create($sess->marker, $sess->visitedAt);
    }

    private function getDebugData(string $AccessToken, string $SessionToken): array
    {
        $data['Session'] = $this->sess;
        $data['accessTokenData'] = Token::toArray($AccessToken);
        $data['sessionTokenData'] = Token::toArray($SessionToken);
        $data['accounts'] = AccountList::byDevice($this->device->id)
            ->excludeDefaults()
            ->initData()
            ->getList();
        return $data;
    }


    /**
     * @param int[] $powers
     * @return array
     * @throws AuthErr
     */
    public function getTokensForResponse(array $powers): array
    {
        $SessionToken = $this->getSessionToken();
        $AccessToken = $this->getAccessToken($powers)->jwt;

        $data = [
            'SessionToken' => $SessionToken,
            'AccessToken'  => $AccessToken,
            'device' => $this->device,
            'curAccount' => $this->account->initData(),
        ];

        if(!empty($this->mergeToken)){
            $data['mergeToken'] = $this->mergeToken;
        }

        if(Env::isDebugMode()){
            $data += $this->getDebugData($AccessToken, $SessionToken);
        }
        return $data;
    }
}