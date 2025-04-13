<?php

namespace Symphograph\Bicycle\Auth\Session;

use Symphograph\Bicycle\Auth\DTOCookieTrait;
use Symphograph\Bicycle\DTO\DTOTrait;

class SessionDTO
{
    use DTOTrait;
    use DTOCookieTrait;
    const string tableName  = 'sessions';
    const string cookieName = 'Beethoven';

    public int     $id;
    public string  $marker;
    public int     $accountId;
    public int     $deviceId;
    public string  $client;
    public ?string $token;
    public ?string $firstIp;
    public ?string $lastIp;
    public ?string $createdAt;
    public ?string $visitedAt;
}