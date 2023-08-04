<?php

namespace Symphograph\Bicycle\Token;

class AccessTokenData extends TokenData
{
    public int $userId;
    public array $powers;
    public string $authType;
    public function __construct()
    {
        parent::__construct();
        $jwtArr = Token::toArray($_SERVER['HTTP_ACCESSTOKEN']);
        $this->userId = $jwtArr['uid'];
        $this->powers = $jwtArr['powers'];
        $this->authType = $jwtArr['authType'];
    }

}