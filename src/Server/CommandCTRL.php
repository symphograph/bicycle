<?php

namespace Symphograph\Bicycle\Server;

use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\Api\Response;
use Symphograph\Bicycle\Auth\User\User;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Errors\MyErrors;
use Symphograph\Bicycle\HTTP\Request;
use Symphograph\Bicycle\Repo\Redis\RedisDB;
use Symphograph\Bicycle\Repo\Redis\RedisTTL;

class CommandCTRL
{
    public static function restartAllWorkers(): void
    {
        User::auth([1]);
        Request::checkEmpty(['anyData']);

        $key = 'supervisor:restart:all';

        if (RedisDB::exists($key)) {
            $left = RedisDB::getTTL($key) ?? 0;
            $msg = "Restart already in progress. Retry in {$left}s.";
            throw new AppErr($msg, httpStatus: 409);
        }

        RedisDB::setex($key, 1, RedisTTL::from(30));

        try {
            new Commander()->restartAllWorkers();
        } catch (\Throwable $e) {
            RedisDB::del($key); // ошибка — пусть можно сразу повторить
            throw $e;
        }

        Response::success();
    }

    #[NoReturn] public static function flushRedis(): void
    {
        User::auth([1]);
        Request::checkEmpty(['anyData']);

        RedisDB::flushAll();
        Response::success();
    }
}