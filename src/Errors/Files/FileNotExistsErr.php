<?php

namespace Symphograph\Bicycle\Errors\Files;

class FileNotExistsErr extends FileErr
{
    public function __construct(string $message = 'File does Not Exists')
    {
        parent::__construct($message, 'Файл не найден');
    }
}