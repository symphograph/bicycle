<?php

namespace Symphograph\Bicycle\Api\VK\Errors;

use Symphograph\Bicycle\Errors\MyErrors;
use Symphograph\Bicycle\Helpers;

class InvalidVKToken extends MyErrors
{
    public function __construct(
        string $message = 'InvalidVKToken',
        string $pubMsg = 'Ошибка авторизации в VK',
        int $httpStatus = 401
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}