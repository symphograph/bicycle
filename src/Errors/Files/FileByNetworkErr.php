<?php

namespace Symphograph\Bicycle\Errors\Files;

class FileByNetworkErr extends FileErr
{
    public function __construct(
        string $message = 'FileByNetworkErr',
        string $pubMsg = 'Ошибка сети',
        int $httpStatus = 500
    )
    {
        parent::__construct($message, $pubMsg, $httpStatus);
    }
}