<?php

namespace Symphograph\Bicycle\Errors;

use Symphograph\Bicycle\Helpers;

class FileErr extends MyErrors
{
    public function __construct(
        string $message = 'File error',
        string $pubMsg = 'Ошибка файла',
        int $httpStatus = 500
    )
    {
        $this->type = Helpers::classBasename(self::class);
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}