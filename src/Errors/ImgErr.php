<?php

namespace Symphograph\Bicycle\Errors;

class ImgErr extends MyErrors
{
    public function __construct(
        string $message = 'Img conversation error',
        string $pubMsg = 'Ошибка при конвертации',
        int $httpStatus = 500
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}