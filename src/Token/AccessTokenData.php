<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Env\Server\ServerEnv;

class AccessTokenData extends TokenData
{
    public int $userId;
    public int $accountId;
    public array $powers;
    public string $authType;
    public string $avaFileName;

    public function __construct(string $jvt)
    {
        parent::__construct($jvt);
        AccessToken::validation($jvt);
        $jwtArr = Token::toArray($jvt);
        $this->userId = $jwtArr['uid'];
        $this->accountId = $jwtArr['accountId'];
        $this->powers = $jwtArr['powers'];
        $this->authType = $jwtArr['authType'];
        $this->avaFileName = $jwtArr['avaFileName'];
    }

    public static function accountId(): int
    {
        $tokenData = new self(ServerEnv::HTTP_ACCESSTOKEN());
        return $tokenData->accountId;
    }

    public static function userId(): int
    {
        $tokenData = new self(ServerEnv::HTTP_ACCESSTOKEN());
        return $tokenData->userId;
    }

    public static function authType(): string
    {
        $tokenData = new self(ServerEnv::HTTP_ACCESSTOKEN());
        return $tokenData->authType;
    }

    public static function avaFileName(): string
    {
        $tokenData = new self(ServerEnv::HTTP_ACCESSTOKEN());
        return $tokenData->avaFileName;
    }
}