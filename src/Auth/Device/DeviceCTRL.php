<?php

namespace Symphograph\Bicycle\Auth\Device;

use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\Api\Response;
use Symphograph\Bicycle\Auth\Session\Session;
use Symphograph\Bicycle\Auth\User\User;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\Errors\ValidationErr;
use Symphograph\Bicycle\HTTP\Request;
use Symphograph\Bicycle\Token\AccessToken;

class DeviceCTRL extends Device
{
    /**
     * @throws AccessErr
     * @throws AuthErr
     * @throws ValidationErr
     */
    public static function listByUserId(): void
    {
        User::auth();
        Request::checkEmpty(['userId']);
        $userId = AccessToken::byHTTP([])->userId;
        if($userId !== $_POST['userId']) {
            throw new AccessErr();
        }
        $devices = DeviceList::byUserId($_POST['userId'])->getList();
        Response::data($devices);
    }


    /**
     * @throws AccessErr|AuthErr|ValidationErr
     */
    #[NoReturn] public static function unlinkUser(): void
    {
        User::auth();
        Request::checkEmpty(['deviceId']);
        $userId = AccessToken::byHTTP([])->userId;
        $device = Device::byId($_POST['deviceId']);
        $device->unlinkFromUser($userId);
        Session::delByDevice($device->id);
        Response::success();
    }


    /**
     * @throws AccessErr|AuthErr|ValidationErr
     */
    #[NoReturn] public static function unlinkAccount(): void
    {
        User::auth();
        Request::checkEmpty(['accountId']);

        $device = Device::byCookie();
        $device->unlinkFromAccount($_POST['accountId']);
        Response::success();
    }

    public static function exit(): void
    {
        printr($_COOKIE);
        return;
    }
}