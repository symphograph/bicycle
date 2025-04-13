<?php

namespace Symphograph\Bicycle\Errors;

class CooldownNotExpiredErr extends AppErr
{
    public function __construct(int $minutesBefore)
    {
        $msg = "Before next try: $minutesBefore min.";
        $pubMsg = "До следующей попытки: $minutesBefore мин.";
        parent::__construct($msg, $pubMsg, 429);
    }
}