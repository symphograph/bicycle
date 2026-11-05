<?php

namespace Symphograph\Bicycle\Errors\Socket;

use Symphograph\Bicycle\Errors\MyErrors;

class UnknownWSEventErr extends MyErrors
{
    public function __construct(string $wsEventName)
    {
        $message = "Unknown socket event: $wsEventName";
        parent::__construct($message, 'Socket Error');
    }
}