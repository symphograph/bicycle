<?php

namespace Symphograph\Bicycle\Errors;

class CurlErr extends MyErrors
{
    public function __construct(string $message = 'CurlErr', string $pubMsg = 'Межсерверная ошибка', int $httpStatus = 500)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}