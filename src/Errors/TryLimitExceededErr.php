<?php

namespace Symphograph\Bicycle\Errors;

class TryLimitExceededErr extends AppErr
{
    public function __construct()
    {
        $msg = "Try limit exceeded.";
        $pubMsg = "Превышено количество попыток.";
        parent::__construct($msg, $pubMsg, 429);
    }
}