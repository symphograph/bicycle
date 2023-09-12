<?php

namespace Symphograph\Bicycle\Errors;

class AccessErr extends MyErrors
{
    protected string $type = 'AccessErr';

    public function __construct(string $message = 'AccessErr', string $pubMsg = 'Недостаточно прав', protected int $httpStatus = 403)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}