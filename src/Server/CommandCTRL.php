<?php

namespace Symphograph\Bicycle\Server;

use Symphograph\Bicycle\Api\Response;
use Symphograph\Bicycle\Auth\User\User;
use Symphograph\Bicycle\Errors\MyErrors;
use Symphograph\Bicycle\HTTP\Request;

class CommandCTRL
{
    public static function restartAllWorkers(): void
    {
        User::auth([1]); // не пропустит чужой запрос
        Request::checkEmpty(['anyData']);
        $cmd = new Commander();
        $res = $cmd->restartAllWorkers();
        if (!$res) throw new MyErrors("restartAllWorkers error");
        Response::success();
    }
}