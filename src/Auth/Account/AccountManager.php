<?php

namespace Symphograph\Bicycle\Auth\Account;


use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Auth\Account\Profile\AccProfileITF;
use Symphograph\Bicycle\PDO\DB;
use Symphograph\Bicycle\PDO\PutMode;

class AccountManager
{
    private function __construct(public Account $account, public ?AccProfileITF $profile = null)
    {
        if($account->authType === AccountType::Default->value) return;
        if(empty($profile)) throw new AppErr('Profile not set');
    }

    public static function create(AccountType $authType, int $userId, ?AccProfileITF $profile = null): static
    {
        $account = Account::create($authType, $userId);
        $account->putToDB(PutMode::insert);
        $account->id = DB::lastId();

        if($profile !== null) {
            $profile->setAccountId($account->id);
            $profile->putToDB(PutMode::insert);
        }

        return new static($account, $profile);
    }


}