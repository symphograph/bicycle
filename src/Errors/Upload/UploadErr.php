<?php

namespace Symphograph\Bicycle\Errors\Upload;

use Symphograph\Bicycle\Errors\MyErrors;
use Symphograph\Bicycle\Helpers;

class UploadErr extends MyErrors
{
    public function __construct(
        string $message = 'Upload error',
        string $pubMsg = 'Ошибка при загрузке',
        int $httpStatus = 500
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}