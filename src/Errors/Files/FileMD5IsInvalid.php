<?php

namespace Symphograph\Bicycle\Errors\Files;

class FileMD5IsInvalid extends FileErr
{
    public function __construct(
        string $md5,
    )
    {
        $msg = "$md5 is not md5";
        parent::__construct($msg);
    }
}