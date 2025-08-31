<?php

namespace Symphograph\Bicycle\Errors\Email;

class CodeAlreadySentErr extends EmailErr
{

    public function __construct(int $minutesBefore)
    {
        $msg = "Before next try: $minutesBefore min.";
        $pubMsg = "До следующей попытки: $minutesBefore мин.";

        parent::__construct($msg, $pubMsg, 429);
        $this->payload = ['minutes' => $minutesBefore];
    }
}