<?php

namespace Symphograph\Bicycle\Errors;

class DelRestrictErr extends MyErrors
{
    public function __construct(
        string $message,
        string $pubMsg = 'Объект используется. Нельзя удалить',
        int $httpStatus = 500
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}