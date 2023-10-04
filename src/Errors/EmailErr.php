<?php

namespace Symphograph\Bicycle\Errors;

use Symphograph\Bicycle\Helpers;

class EmailErr extends ValidationErr
{
    public function __construct(
        string $message = 'invalid email',
        string $pubMsg = 'Пожалуйста, введите корректный email.',
        int $httpStatus = 400
    )
    {
        $this->type = Helpers::classBasename(self::class);
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}