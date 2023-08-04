<?php

namespace Symphograph\Bicycle\Token;

use DateTimeImmutable;

class TokenData
{
    public string $issuer;
    public array $audience;
    public string $subject;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $expireAt;

    public function __construct()
    {
        $jwtArr = Token::toArray($_SERVER['HTTP_ACCESSTOKEN']);
        $this->issuer = $jwtArr['iss'];
        $this->audience = $jwtArr['aud'];
        $this->subject = $jwtArr['sub'];
        $this->createdAt = $jwtArr['iat'];
        $this->expireAt = $jwtArr['exp'];
    }
}