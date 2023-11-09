<?php

namespace Symphograph\Bicycle\Errors;

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
        $this->type = Helpers::classBasename(self::class);
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}