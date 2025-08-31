<?php

namespace Symphograph\Bicycle\Errors;

class NoContentErr extends MyErrors
{
    public bool $loggable = true;

    public function __construct(string $message = 'dataIsEmpty', string $pubMsg = 'Нет данных', int $httpStatus = 406)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}