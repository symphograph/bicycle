<?php

namespace Symphograph\Bicycle\Api;

use Symphograph\Bicycle\DTO\BindTrait;

class ResponseErr
{
    use BindTrait;

    public string $msg;
    public string $error;
    public int    $httpStatus = 500;
    public array  $trace      = [];
    public string $type       = 'Err';
    public mixed  $payload    = [];


}