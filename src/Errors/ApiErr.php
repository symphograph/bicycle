<?php

namespace Symphograph\Bicycle\Errors;

class ApiErr extends MyErrors
{
    public function __construct(string $message = 'Unknown method', string $pubMsg = 'Я так не умею', int $httpStatus = 400)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}