<?php

namespace Symphograph\Bicycle\Auth\Account\Profile;

abstract class AccProfileDTO implements AccProfileITF
{
    public int $accountId;

    public function setAccountId(int $accountId): void
    {
        $this->accountId = $accountId;
    }

    protected function nickByNames(): string
    {
        $nickName = ($this->firstName ?? '') . ' ' . ($this->lastName ?? '');
        return trim($nickName);
    }

}