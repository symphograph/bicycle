<?php

namespace Symphograph\Bicycle\Auth\Account;

use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\Api\Response;
use Symphograph\Bicycle\Auth\Device\Device;
use Symphograph\Bicycle\Auth\User\User;
use Symphograph\Bicycle\Env\Services\Client;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\Account\AccountNoExistsErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\Errors\NoContentErr;
use Symphograph\Bicycle\Errors\ValidationErr;
use Symphograph\Bicycle\HTTP\Request;
use Symphograph\Bicycle\Token\AccessToken;

class AccountCTRL
{
    /**
     * @throws AuthErr
     * @throws AccessErr
     * @throws AccountNoExistsErr
     */
    public static function get(): void
    {
        User::auth();
        $accessToken = AccessToken::byHTTP([]);
        $Account = Account::byId($accessToken->accountId)
            or throw new AccountNoExistsErr($accessToken->accountId);

        Response::data($Account->initData());
    }

    public static function byId(): void
    {
        Client::authServer();
        $accountId = intval($_POST['accountId'] ?? 0) or throw new ValidationErr();
        $Account = Account::byId($accountId) or throw new NoContentErr();
        Response::data($Account);
    }

    /**
     * @throws AccessErr
     * @throws AuthErr
     * @throws ValidationErr
     * @throws AppErr
     */
    public static function unlinkFromUser(): void
    {
        User::auth();
        Request::checkEmpty(['accountId']);

        $token = AccessToken::byHTTP([]);
        $account = Account::byId($_POST['accountId']);
        if($account->userId !== $token->userId){
            throw new AppErr("Invalid userId");
        }

        $account->unlinkFromUser();

        Response::success();
    }

    /**
     * @throws AuthErr
     * @throws AccessErr
     */
    #[NoReturn] public static function listByDevice(): void
    {
        User::auth();
        $Device = Device::byCookie();
        $accList = AccountList::byDevice($Device->id)
            ->excludeDefaults()
            ->initData();

        $userId = AccessToken::byHTTP([])->userId;
        if(!$accList->isContainsUser($userId)){
            throw new AccessErr();
        }

        Response::data($accList->getList());
    }

    /**
     * @throws AuthErr
     * @throws AccessErr
     */
    #[NoReturn] public static function selfList(): void
    {
        User::auth();

        $userId = AccessToken::byHTTP([])->userId;
        $accList = AccountList::byUser($userId)
            ->excludeDefaults()
            ->initData();

        Response::data($accList->getList());
    }

    #[NoReturn] public static function transfer(): void
    {
        Client::authServer();
        $AccountDTO = new AccountDTO();
        $AccountDTO->bindSelf($_POST['account']);
        Response::success();
    }

    /**
     * @throws AuthErr
     * @throws AccessErr
     * @throws ValidationErr
     */
    #[NoReturn] public static function listByUserId(): void
    {
        User::auth([1]);
        Request::checkEmpty(['userId']);

        $accList = AccountList::byUser($_POST["userId"])
            ->excludeDefaults()
            ->initData();

        Response::data($accList->getList());
    }

    #[NoReturn] public static function groupedByUser(): void
    {
        User::auth([1]);
        $accList = AccountList::allNoDefaults();
        $users = [];
        foreach($accList->getList() as $account){
            $users[$account->userId][] = $account->initData();
        }
        Response::data(array_values($users));
    }

}