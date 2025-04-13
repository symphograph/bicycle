<?php

namespace Symphograph\Bicycle\Auth\Account;


use Symphograph\Bicycle\DTO\DTOTrait;

class AccountDTO
{
    use DTOTrait;
    const string tableName = 'accounts';

    public int     $id;
    public int     $userId;
    public string  $authType;
    public string  $createdAt;
    public string  $visitedAt;
    public ?string $avaFileName;

}