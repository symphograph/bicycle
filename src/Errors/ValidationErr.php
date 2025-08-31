<?php

namespace Symphograph\Bicycle\Errors;

class ValidationErr extends MyErrors
{
    public function __construct(
        string $message = 'invalid data',
        string $pubMsg = 'Ошибка данных',
        int $httpStatus = 400
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}