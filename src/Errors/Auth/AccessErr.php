<?php

namespace Symphograph\Bicycle\Errors\Auth;

use Symphograph\Bicycle\Errors\MyErrors;

class AccessErr extends MyErrors
{
    public function __construct(
        string $message = 'AccessErr',
        string $pubMsg = 'Недостаточно прав',
        protected int $httpStatus = 403)
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}