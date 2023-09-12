<?php

namespace Symphograph\Bicycle\Token;

class SessionTokenData extends TokenData
{
    public string $marker;

    public function __construct(string $jvt)
    {
        parent::__construct($jvt);
        $jwtArr = Token::toArray($jvt);
        $this->marker = $jwtArr['jti'];
    }

}