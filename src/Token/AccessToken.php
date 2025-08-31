<?php

namespace Symphograph\Bicycle\Token;


use Symphograph\Bicycle\Auth\Account\AccountType;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;

readonly class AccessToken
{

    private function __construct(
        public string $jwt,
        public string $sessionMark,
        public int $userId,
        public int $accountId,
        public array $powers,
        public AccountType $authType
    ){}

    public function arr(): array
    {
        return Token::toArray($this->jwt);
    }

    /**
     * @throws AuthErr
     */
    public static function create(
        string $sessionMark,
        int    $userId,
        int    $accountId,
        array  $powers,
        string $createdAt = 'now',
        AccountType $authType = AccountType::Default,
    ): self
    {
        $audience = Env::getJWT()->audience;

        $audience[] = ServerEnv::SERVER_NAME();

        $claims = [
            'accountId'   => $accountId,
            'sessionMark' => $sessionMark,
            'powers'      => $powers,
            'authType'    => $authType->value,
        ];

        $Token = new Token(
            uid: $userId,
            aud: $audience,
            createdAt: $createdAt,
            expireDuration: '+30 minutes',
            claims: $claims
        );
        return new self($Token->jwt, $sessionMark, $userId, $accountId, $powers, $authType);
    }

    /**
     * @throws AuthErr
     * @throws AccessErr
     */
    private function validation($needPowers = [], bool $ignoreExpire = false): void
    {
        Token::validation(
            jwt: $this->jwt,
            ignoreExpire: $ignoreExpire
        );

        $this->checkPowers($needPowers);
    }

    /**
     * @throws AccessErr
     */
    private function checkPowers(array $needPowers): void
    {
        if (empty($needPowers)) return;

        $intersect = array_intersect($needPowers, $this->powers);
        if (!count($intersect)) throw new AccessErr('Token has not required Powers');
        if(in_array(1, $needPowers)){
            if(!Env::isDebugIp()) throw new AccessErr();
        }
    }

    /**
     * @throws AccessErr
     * @throws AuthErr
     */
    public static function byJwt(string $jwt, $needPowers, bool $ignoreExpire = false): self
    {
        $arr = Token::toArray($jwt);
        $authType = AccountType::from($arr['authType']);
        $token = new self($jwt, $arr['sessionMark'], $arr['uid'], $arr['accountId'], $arr['powers'], $authType);
        $token->validation($needPowers, $ignoreExpire);
        return $token;
    }

    /**
     * @throws AuthErr
     * @throws AccessErr
     */
    public static function byHTTP($needPowers, bool $ignoreExpire = false): self
    {
         $jwt = $_SERVER['HTTP_ACCESSTOKEN']
            ?? throw new AuthErr('Unauthorized');
         return self::byJwt($jwt, $needPowers, $ignoreExpire);
    }

}