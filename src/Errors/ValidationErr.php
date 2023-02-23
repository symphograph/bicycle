<?php

namespace Symphograph\Bicycle\Errors;

class ValidationErr extends MyErrors
{
    protected string $type = 'ValidationErr';
    protected bool $loggable = true;

    public function __construct(string $message, string $pubMsg = 'Ошибка данных', int $httpStatus = 400)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}