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

    public static function all(): static
    {
        $sql = "SELECT * FROM accounts";
        return static::bySql($sql);
    }

    public static function allNoDefaults(): static
    {
        $sql = <<<SQL
            SELECT * FROM accounts  
            WHERE authType <> 'default'
            ORDER BY userId, visitedAt DESC;
        SQL;
        return static::bySql($sql);
    }

    /**
     * @param int $deviceId
     * @return self
     */
    public static function byDevice(int $deviceId): self
    {
        $sql = "
            select accounts.* from accounts 
            inner join device_account 
                on accounts.id = device_account.accountId
                and device_account.deviceId = :deviceId";

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

    public function isContainsUser(int $userId): bool
    {
        $fn = fn($account) => $account->userId === $userId;
        return array_any($this->list, $fn);
    }

}