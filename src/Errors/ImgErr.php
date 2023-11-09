<?php

namespace Symphograph\Bicycle\Errors;


use Symphograph\Bicycle\Helpers;

class ImgErr extends MyErrors
{
    public function __construct(
        string $message = 'Img conversation error',
        string $pubMsg = 'Ошибка при конвертации',
        int $httpStatus = 500
    )
    {
        $this->type = Helpers::classBasename(self::class);
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}