<?php

namespace Symphograph\Bicycle\Errors\Files;

class InvalidMD5 extends FileErr
{
    public function __construct(
        string $message = 'It is not md5',
        string $pubMsg = 'Ошибка работы с файлом',
        int $httpStatus = 500
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}