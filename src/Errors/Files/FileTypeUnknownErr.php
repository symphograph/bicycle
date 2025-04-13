<?php

namespace Symphograph\Bicycle\Errors\Files;

class FileTypeUnknownErr extends FileErr
{
    public function __construct(string $type)
    {
        $msg = "Unknown file type: $type";
        parent::__construct($msg, 'Неизвестный тип файла');
    }
}