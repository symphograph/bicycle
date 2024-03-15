<?php

namespace Symphograph\Bicycle\Errors\Upload;

use Symphograph\Bicycle\Helpers;

class AlreadyExistsErr extends UploadErr
{
    public function __construct(
        string $message = 'File already exists',
        string $pubMsg = 'Файл уже существует',
        int $httpStatus = 400
    )
    {
        //$this->type = Helpers::classBasename(self::class);
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}