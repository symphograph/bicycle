<?php

namespace Symphograph\Bicycle\Errors;

class AuthErr extends MyErrors
{
    protected string $type = 'AuthErr';
    public function __construct(string $message, string $pubMsg = 'Ошибка авторизации', int $httpStatus = 500)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}