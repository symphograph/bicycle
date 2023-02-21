<?php

namespace Symphograph\Bicycle\Errors;

use Throwable;

class DatabaseErr extends MyErrors
{
    protected $Trace = [];
    public function __construct(Throwable $err, protected string $sql = '', protected array $args = [])
    {
        // parent::__construct($err->getMessage());
    }
}