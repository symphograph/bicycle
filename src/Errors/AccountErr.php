<?php

namespace Symphograph\Bicycle\Errors;

class AccountErr extends MyErrors
{
    protected string $type = 'AccountErr';

    public function __construct(string $message = 'AccountErr', string $pubMsg = 'Ошибка Аккаунта', protected int $httpStatus = 500)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}