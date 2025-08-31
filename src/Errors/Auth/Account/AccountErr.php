<?php

namespace Symphograph\Bicycle\Errors\Auth\Account;

use Symphograph\Bicycle\Errors\MyErrors;

class AccountErr extends MyErrors
{
    public function __construct(string $message = 'AccountErr', string $pubMsg = 'Ошибка Аккаунта', protected int $httpStatus = 500)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}