<?php

namespace Symphograph\Bicycle\Debug;

use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\Api\Response;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\HTTP\Request;

class DebugCTRL
{
    #[NoReturn] public static function isDebugIp(): void
    {
        Request::checkEmpty(['client']);
        Response::data(['is' => Env::isDebugIp()]);
    }
}