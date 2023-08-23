<?php

namespace Symphograph\Bicycle\Token;

class SessionTokenData extends TokenData
{
    public string $sessionId;

    public function __construct(string $jvt)
    {
        parent::__construct($jvt);
        $jwtArr = Token::toArray($jvt);
        $this->sessionId = $jwtArr['jti'];
    }
}