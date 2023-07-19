<?php

namespace Symphograph\Bicycle\Errors;

class CurlErr extends MyErrors
{
    protected string $type = 'CurlErr';
    public function __construct(string $message = '', string $pubMsg = 'Межсерверная ошибка', int $httpStatus = 500)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}