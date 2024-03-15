<?php

namespace Symphograph\Bicycle\Errors\Upload;

class EmptyFilesErr extends UploadErr
{
    public function __construct(
        string $message = '$_FILES is empty',
        string $pubMsg = 'Файлы не доставлены',
        int $httpStatus = 400
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }

}