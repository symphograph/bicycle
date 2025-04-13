<?php

namespace Symphograph\Bicycle\Errors\Files;

class FileHashIsInvalid extends FileErr
{
    public function __construct(
        string $hash,
    )
    {
        $msg = "$hash is invalid";
        parent::__construct($msg);
    }
}