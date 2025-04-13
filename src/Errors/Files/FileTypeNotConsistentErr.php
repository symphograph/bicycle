<?php

namespace Symphograph\Bicycle\Errors\Files;

class FileTypeNotConsistentErr extends FileErr
{
    public function __construct(string $type, string $ext)
    {
        $msg = "Type $type is not consistent with extension $ext";
        parent::__construct($msg, 'Конфликт расширения и типа файла');
    }
}