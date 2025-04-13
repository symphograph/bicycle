<?php

namespace Symphograph\Bicycle\Auth\Account;

use Symphograph\Bicycle\Auth\Contact\Contact;
use Override;
use Symphograph\Bicycle\DTO\AbstractList;
use Symphograph\Bicycle\Helpers\Arr;

class AccountList extends AbstractList
{

    /**
     * @var Account[]
     */
    protected array $list = [];

    #[Override] public static function getItemClass(): string
    {
        return Account::class;
    }

    /**
     * @param Contact[] $contacts
     * @return self
     */
    public static function byContacts(array $contacts): self
    {
        $AccountList = new self();
        foreach ($contacts as $contact){
            $Account = Account::byContact($contact);
            if($Account) {
                $AccountList->list[] = $Account;
            }
        }
        return $AccountList;
    }

    /**
     * @param int $deviceId
     * @return self
     */
    public static function byDevice(int $deviceId): self
    {
        $sql = "
            select accounts.* from accounts 
            inner join device_user 
                on accounts.userId = device_user.userId
                and device_user.deviceId = :deviceId";

        $params = compact('deviceId');
        return self::bySql($sql, $params);
    }

    public function excludeDefaults(): static
    {
        $arr = [];
        foreach ($this->list as $account){
            if($account->authType === 'default'){
                continue;
            }
            $arr[] = $account;
        }
        $this->list = $arr;
        return $this;
    }

    public static function byUser(int $userId): static
    {
        $sql = "
            select accounts.* 
            from accounts 
            where userId = :userId";
        $params = compact('userId');
        return self::bySql($sql, $params);
    }

    public function sortByCreatedAt(bool $desc = false): void
    {
        $this->list = Arr::sortMultiArrayByProp($this->list, ['createdAt' => $desc ? 'desc' : 'asc']);
    }

    public function sortByVisitedAt(bool $desc = false): void
    {
        $this->list = Arr::sortMultiArrayByProp($this->list, ['visitedAt' => $desc ? 'desc' : 'asc']);
    }

    /**
     * @return Account[]
     */
    public function getList(): array
    {
        return $this->list;
    }

    public function setUserId(int $userId): void
    {
        foreach ($this->list as $account) {
            $account->userId = $userId;
            $account->putToDB();
        }
    }

    public function getFirstUserId(): int|false
    {
        $this->sortByCreatedAt();
        foreach ($this->list as $account) {
            if(!empty($account->userId)) {
                return $account->userId;
            }
        }
        return false;
    }


}