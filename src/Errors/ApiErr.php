<?php

namespace Symphograph\Bicycle\Errors;

class ApiErr extends MyErrors
{
    protected string $type = 'ApiErr';
    public function __construct(string $message = 'Unknown method', string $pubMsg = 'Я так не умею', int $httpStatus = 402)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}