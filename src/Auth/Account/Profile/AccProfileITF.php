<?php

namespace Symphograph\Bicycle\Auth\Account\Profile;


use Symphograph\Bicycle\Auth\Account\AccountType;
use Symphograph\Bicycle\Contact\Contact;
use Symphograph\Bicycle\PDO\PutMode;

interface AccProfileITF
{
    public static function byContact(string $contactValue): ?self;

    public function setAccountId(int $accountId): void;

    public function putToDB(PutMode $mode = PutMode::safeReplace): void;

    public function externalAvaUrl(): string;

    public function nickName(): string;

    public function getAuthType(): AccountType;

    public function getContact(): Contact;
}