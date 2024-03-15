<?php

namespace Symphograph\Bicycle\Errors\Files;

class FileProcessErr extends FileErr
{
    public function __construct(int $fileId, $pubMsg = 'Ошибка при обработке файла')
    {
        $message = $this->buildMsg($fileId);
        parent::__construct($message, $pubMsg);
    }

    private function buildMsg(int $fileId): string
    {
        return "Processing File $fileId was failed";
    }
}