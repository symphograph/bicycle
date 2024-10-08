<?php

namespace Symphograph\Bicycle\Errors;

class AppErr extends MyErrors
{
    public function __construct(
        string $message = 'App error',
        string $pubMsg = 'Ошибка приложения',
        int $httpStatus = 500
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}