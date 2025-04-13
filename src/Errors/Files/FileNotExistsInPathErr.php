<?php

namespace Symphograph\Bicycle\Errors\Files;

class FileNotExistsInPathErr extends FileNotExistsErr
{
    public function __construct(string $fullPath)
    {
        $msg = "File $fullPath does not Exists";

        parent::__construct($msg);
    }
}