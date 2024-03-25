<?php

namespace Symphograph\Bicycle\Errors\Files;

class FileProcessErr extends FileErr
{
    public function __construct(string $msg, $pubMsg = 'Ошибка при обработке файла')
    {
        parent::__construct($msg, $pubMsg);
    }
}