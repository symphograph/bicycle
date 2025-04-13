<?php

namespace Symphograph\Bicycle\Errors\Email;

class CodeExpiredErr extends EmailErr
{
    public function __construct(
        string $message = 'Email code expired',
        string $pubMsg = 'Срок жизни кода истек',
        int $httpStatus = 410
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}