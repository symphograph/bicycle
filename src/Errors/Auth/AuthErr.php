<?php

namespace Symphograph\Bicycle\Errors\Auth;

use Symphograph\Bicycle\Errors\MyErrors;

class AuthErr extends MyErrors
{
    public function __construct(string $message = 'invalid auth', string $pubMsg = 'Ошибка авторизации', int $httpStatus = 401)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}