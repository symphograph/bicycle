<?php

namespace Symphograph\Bicycle\Errors\Files;

class UnknownTypeErr extends FileErr
{
    public function __construct(
        string $message = 'Unknown file type',
        string $pubMsg = 'Неизвестный тип файла',
        int $httpStatus = 500
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}