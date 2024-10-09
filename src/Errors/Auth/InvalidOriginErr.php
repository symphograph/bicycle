<?php

namespace Symphograph\Bicycle\Errors\Auth;

class InvalidOriginErr extends AuthErr
{
    public function __construct(string $origin = 'Invalid Origin', string $pubMsg = 'Ошибка авторизации')
    {
        $message = "Origin $origin is invalid";
        parent::__construct($message, $pubMsg, 403);
    }
}