<?php

namespace Symphograph\Bicycle\Errors\Files;

use Symphograph\Bicycle\Errors\MyErrors;

class FileErr extends MyErrors
{
    public function __construct(
        string $message = 'File Error',
        string $pubMsg = 'Ошибка работы с файлом',
        int $httpStatus = 500
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}