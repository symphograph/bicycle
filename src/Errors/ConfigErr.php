<?php

namespace Symphograph\Bicycle\Errors;

class ConfigErr extends MyErrors
{
    protected string $type = 'ConfigErr';

    public function __construct(string $message, string $pubMsg = '', int $httpStatus = 500)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}