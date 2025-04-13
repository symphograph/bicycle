<?php

namespace Symphograph\Bicycle\Errors\Files;

class FileNotExistsInDBErr extends FileNotExistsErr
{
    public function __construct(string|int $hashOrId)
    {
        $msg = "File $hashOrId does not Exists";
        parent::__construct($msg);
    }
}