<?php

namespace Symphograph\Bicycle\Errors;

class NoContentErr extends MyErrors
{
    protected string $type = 'NoContent';
    protected bool $loggable = false;

    public function __construct(string $message = 'dataIsEmpty', string $pubMsg = 'Нет данных', int $httpStatus = 202)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}