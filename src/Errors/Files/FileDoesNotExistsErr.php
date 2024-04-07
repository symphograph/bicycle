<?php

namespace Symphograph\Bicycle\Errors\Files;

class FileDoesNotExistsErr extends FileErr
{
    public function __construct(
        string $fullPath = 'File does not Exists',
    )
    {
        $message = "File $fullPath does not Exists";
        $pubMsg = 'Файл не найден';
        parent::__construct($message, $pubMsg, 404);
    }
}