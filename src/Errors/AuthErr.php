<?php

namespace Symphograph\Bicycle\Errors;

class AuthErr extends MyErrors
{
    protected string $type = 'AuthErr';
    protected bool $loggable = false;
}