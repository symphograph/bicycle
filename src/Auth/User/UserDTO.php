<?php

namespace Symphograph\Bicycle\Auth\User;

use Symphograph\Bicycle\Auth\DTOCookieTrait;
use Symphograph\Bicycle\DTO\DTOTrait;

class UserDTO
{
    use DTOTrait;
    use DTOCookieTrait;
    const string tableName  = 'users';
    const string cookieName = 'Haydn';

    public int     $id;
    public string  $createdAt;
    public string  $visitedAt;
    public ?string  $publicNick;
    public int $cpu;
}