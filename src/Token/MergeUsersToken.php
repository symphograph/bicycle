<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\Auth\AuthErr;


readonly class MergeUsersToken
{

    private function __construct(
        public int    $fromUserId,
        public int    $toUserId,
        public string $jwt,
    ){}

    public static function create(int $fromUserId, int $toUserId): self
    {
        $claims = [
            'fromUserId' => $fromUserId,
            'toUserId' => $toUserId
        ];
        $Token = new Token(
            aud: [ServerEnv::SERVER_NAME()],
            expireDuration: "+5 min",
            claims: $claims,
        );
        return new self($fromUserId, $toUserId, $Token->jwt);
    }

    /**
     * @throws AuthErr
     */
    public static function byJwt(string $jwt): self
    {
        Token::validation($jwt);
        $arr = Token::toArray($jwt);
        return new self($arr['fromUserId'], $arr['toUserId'], $jwt);
    }

}