<?php

namespace Symphograph\Bicycle\Errors;

use Symphograph\Bicycle\Helpers;

class AppErr extends MyErrors
{
    public function __construct(
        string $message = 'App error',
        string $pubMsg = 'Ошибка приложения',
        int $httpStatus = 500
    )
    {
        $this->type = Helpers::classBasename(self::class);
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}