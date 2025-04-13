<?php

namespace Symphograph\Bicycle\Errors\Email;

use Symphograph\Bicycle\Errors\MyErrors;

class EmailErr extends MyErrors
{
    public function __construct(
        string $message = 'Mail error',
        string $pubMsg = 'Ошибка работы с почтой',
        int $httpStatus = 500
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}