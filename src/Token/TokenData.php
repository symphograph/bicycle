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

    public function __construct(string $jwt)
    {
        $jwtArr = Token::toArray($jwt);
        $this->issuer = $jwtArr['iss'];
        $this->audience = $jwtArr['aud'];
        $this->subject = $jwtArr['sub'];
        $this->createdAt = $jwtArr['iat'];
        $this->expireAt = $jwtArr['exp'];
    }
}