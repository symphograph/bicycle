<?php

namespace Symphograph\Bicycle\Auth\Account\Repo;

use Symphograph\Bicycle\Auth\Account\Account;
use Symphograph\Bicycle\Errors\AppErr;

class AccountRepoDB
{
    public static function byId(int $id, bool $required): ?Account
    {
        return Account::byId($id)
            ?? $required ? throw new AppErr("Account $id not found") : null;
    }

    public static function insert(Account $account): void
    {

    }
}