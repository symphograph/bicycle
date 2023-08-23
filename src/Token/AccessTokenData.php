<?php

namespace Symphograph\Bicycle\Token;

class AccessTokenData extends TokenData
{
    public int $userId;
    public array $powers;
    public string $authType;

    public function __construct(string $jvt)
    {
        parent::__construct($jvt);
        $jwtArr = Token::toArray($jvt);
        $this->userId = $jwtArr['uid'];
        $this->powers = $jwtArr['powers'];
        $this->authType = $jwtArr['authType'];
    }

}