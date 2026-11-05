<?php

namespace Symphograph\Bicycle\Token;

use Symphograph\Bicycle\Auth\Account\AccountType;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\Logs\Log;
use Symphograph\Bicycle\Powers\PowEnumITF;

readonly class AccessToken
{
    const array needClaims = ['sessionMark', 'uid', 'accountId', 'deviceId', 'powers', 'authType'];

    private function __construct(
        public string      $jwt,
        public string      $sessionMark,
        public int         $userId,
        public int         $accountId,
        public int         $deviceId,
        public array       $powers,
        public AccountType $authType,
        public array       $extraClaims = [],
    )
    {
    }

    public function toArray(): array
    {
        return Token::toArray($this->jwt);
    }

    public static function create(
        string      $sessionMark,
        int         $userId,
        int         $accountId,
        int         $deviceId,
        array       $powers,
        string      $createdAt = 'now',
        AccountType $authType = AccountType::Default,
        array       $extraClaims = [],
    ): self
    {
        $audience = Env::getJWT()->audience;
        $audience[] = ServerEnv::SERVER_NAME();

        $claims = array_merge([
            'accountId'   => $accountId,
            'sessionMark' => $sessionMark,
            'powers'      => $powers,
            'authType'    => $authType->value,
            'deviceId'    => $deviceId,
        ], $extraClaims);

        $token = new Token(
            uid: $userId,
            aud: $audience,
            createdAt: $createdAt,
            expireDuration: '+1 hour',
            claims: $claims
        );

        return new self(
            jwt: $token->jwt,
            sessionMark: $sessionMark,
            userId: $userId,
            accountId: $accountId,
            deviceId: $deviceId,
            powers: $powers,
            authType: $authType,
            extraClaims: $extraClaims
        );
    }

    public function hasPower(array $needPowers): bool
    {
        if (empty($needPowers)) {
            return true;
        }

        $needPowers = array_map(
            fn($p) => $p instanceof PowEnumITF ? $p->id() : (int)$p,
            $needPowers
        );

        return array_intersect($needPowers, $this->powers) !== [];
    }

    /**
     * @throws AuthErr
     * @throws AccessErr
     */
    public static function byJwt(string $jwt, array $needPowers = [], bool $ignoreExpire = false): self
    {
        Token::validation($jwt, $ignoreExpire);
        $payload = Token::toArray($jwt);
        self::validatePayload($payload);

        $authType = AccountType::from($payload['authType']);

        $token = new self(
            jwt: $jwt,
            sessionMark: $payload['sessionMark'],
            userId: $payload['uid'],
            accountId: $payload['accountId'],
            deviceId: $payload['deviceId'],
            powers: $payload['powers'],
            authType: $authType
        );

        $token->ensurePowers($needPowers);
        return $token;
    }

    /**
     * @throws AuthErr
     * @throws AccessErr
     */
    public static function byHttp(array $needPowers = [], bool $ignoreExpire = false): self
    {
        $jwt = $_SERVER['HTTP_ACCESSTOKEN'] ?? throw new AuthErr('Unauthorized');
        return self::byJwt($jwt, $needPowers, $ignoreExpire);
    }

    public static function isset(): bool
    {
        return isset($_SERVER['HTTP_ACCESSTOKEN']);
    }

    /**
     * @throws AuthErr
     */
    private static function validatePayload(array $payload): void
    {
        foreach (static::needClaims as $claim) {
            if (!isset($payload[$claim])) {
                throw new AuthErr("Missing $claim in accessToken");
            }
        }
    }

    /**
     * @throws AccessErr
     */
    private function ensurePowers(array $needPowers): void
    {

        //Log::msg('powers', $needPowers);
        if (empty($needPowers)) {
            return;
        }

        $needPowers = array_map(
            fn($p) => $p instanceof PowEnumITF ? $p->id() : (int)$p,
            $needPowers
        );

        if (array_intersect($needPowers, $this->powers) === []) {
            throw new AccessErr('Token has not required Powers');
        }

        if (in_array(1, $needPowers, true) && !Env::isDebugIp()) {
            throw new AccessErr();
        }

        if(empty($this->powers)) {

            throw new AccessErr('Powers empty');
        }

    }

    public function claim(string $name): mixed
    {
        return $this->extraClaims[$name] ?? null;
    }
}