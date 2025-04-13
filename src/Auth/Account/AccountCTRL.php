<?php

namespace App\Auth\Account;

use App\Auth\Contact\Contact;
use App\Auth\Device\Device;
use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\Api\Response;
use Symphograph\Bicycle\Env\Services\Client;
use Symphograph\Bicycle\Errors\AccountErr;
use Symphograph\Bicycle\Errors\NoContentErr;
use Symphograph\Bicycle\Errors\ValidationErr;
use Symphograph\Bicycle\Token\AccessTokenData;

class AccountCTRL
{
    public static function get(): void
    {
        $accountId = AccessTokenData::accountId();
        $Account = Account::byIdAndInit($accountId)
            or throw new AccountErr('not exist', 'Аккаунт не найден');

        Response::data($Account);
    }

    public static function byId(): void
    {
        Client::authServer();
        $accountId = intval($_POST['accountId'] ?? 0) or throw new ValidationErr();
        $Account = Account::byId($accountId) or throw new NoContentErr();
        Response::data($Account);
    }

    #[NoReturn] public static function list(): void
    {
        $Device = Device::byCookie();
        $AccountList = AccountList::byDevice($Device->id);
        $AccountList->initData();

        Response::data($AccountList->getList());
    }

    #[NoReturn] public static function transfer(): void
    {
        Client::authServer();
        $AccountDTO = new AccountDTO();
        $AccountDTO->bindSelf($_POST['account']);
        Response::success();
    }

    public static function getByContact(): void
    {
        Client::authServer();
        $contact = new Contact();
        $contact->type = $_POST['contactType'] ?? throw new ValidationErr();
        $contact->value = $_POST['contactValue'] ?? throw new ValidationErr();

        $Account = Account::byContact($contact)
        or throw new NoContentErr();
        Response::data($Account);
    }

    public static function listByContacts(): void
    {
        Client::authServer();
        $contactList = $_POST['contactList'] ?? throw new ValidationErr();
        if(!is_array($contactList)){
            throw new ValidationErr();
        }

        $contacts = Contact::listByBind($contactList);
        $AccountList = AccountList::byContacts($contacts);
        Response::data($AccountList->getList());
    }

}