<?php

namespace Symphograph\Bicycle\Errors\Auth;

class EmptyOriginErr extends AuthErr
{
    public function __construct(string $message = 'Origin is empty', string $pubMsg = 'Ошибка авторизации')
    {
        parent::__construct($message, $pubMsg);
    }
}