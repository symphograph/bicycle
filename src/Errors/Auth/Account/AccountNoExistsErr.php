<?php

namespace Symphograph\Bicycle\Errors\Auth\Account;

class AccountNoExistsErr extends AccountErr
{
    public function __construct(int $accountId)
    {
        $message = "Account $accountId does not exists";
        $pubMsg = 'Профиль не найден';
        parent::__construct($message, $pubMsg);
    }
}