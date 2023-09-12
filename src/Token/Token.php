<?php

namespace Symphograph\Bicycle\Token;

use DateTimeImmutable;
use Lcobucci\JWT\Encoding\{ChainedFormatter, JoseEncoder};
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\{Builder, Parser};
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\{PermittedFor, RelatedTo, SignedWith};
use Lcobucci\JWT\Validation\Validator;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\AuthErr;
use Throwable;


class Token
{
    public string             $jwt;
    public string $iss; // (issuer) издатель токена
    private DateTimeImmutable $iat; // (issued at) время создания токена
    private DateTimeImmutable $exp; // (expire time) срок действия токена
    private DateTimeImmutable $nbf; // (not before) срок, до которого токен не действителен

    public function __construct(
        public string  $jti, // (JWT id) идентификатор токена
        public ?string $sub = 'auth', // (subject) "тема", назначение токена
        public ?int    $uid = null,
        public ?int    $accountId = null,
        public ?array  $aud = [], // (audience) аудитория, получатели токена
        public string  $createdAt = 'now',
        public string  $expireDuration = '+1 hour',
        public array   $powers = [],
        public string  $authType = 'default',
        public string $avaFileName = 'init_ava.jpg',
        string $iss = '' // (issuer) издатель токена
    )
    {
        try {
            $this->iss = !empty($iss) ? $iss : $_SERVER['SERVER_NAME'];
            self::buildDatetime();
            self::initJWT();
            //self::validation($this->jvt, ignoreExpire: true);
        } catch (Throwable $e) {
            throw new AuthErr($e->getMessage(), 'Ошибка генерации токена', 500);
        }

    }

    private function initJWT(): void
    {
        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));

        $Token = $tokenBuilder
            ->issuedBy($this->iss) // iss (issuer) издатель токена
            ->permittedFor(...$this->aud) // aud (audience) аудитория, получатели токена
            ->identifiedBy($this->jti) // jti (JWT id) идентификатор токена
            ->relatedTo($this->sub ?? 'auth')
            ->issuedAt($this->iat) // iat (issued at) время создания токена
            ->canOnlyBeUsedAfter($this->nbf) // nbf (not before) срок, до которого токен не действителен
            ->expiresAt($this->exp) // exp (expire time) срок действия токена
            ->withClaim('uid', $this->uid)
            ->withClaim('accountId', $this->accountId)
            ->withClaim('powers', $this->powers)
            ->withClaim('authType', $this->authType)
            ->withClaim('avaFileName', $this->avaFileName)
            ->withHeader('foo', 'bar')
            ->getToken(new Sha256(), self::getKey());


        $this->jwt = $Token->toString();
    }

    public static function toArray(string $jvt): array
    {
        $parser = new Parser(new JoseEncoder());
        $token = $parser->parse($jvt);
        assert($token instanceof UnencryptedToken);
        return $token->claims()->all();
    }

    private static function parse(string $jvt): UnencryptedToken
    {
        $parser = new Parser(new JoseEncoder());
        return $parser->parse($jvt);
    }

    public static function validation(
        string  $jwt,
        array   $needPowers = [],
        ?string $expectedSubject = null,
        bool    $ignoreExpire = false
    ): void
    {
        $token = self::parse($jwt);
        $tokenArray = self::toArray($jwt);
        $validator = new Validator();

        match (false) {
            $validator->validate($token, new SignedWith(new Sha256(), self::getKey()))
            => throw new AuthErr('Invalid token key'),

            $validator->validate($token, new RelatedTo($expectedSubject ?? 'auth'))
            => throw new AuthErr('Invalid token subject'),

            !self::isExpired($token, $ignoreExpire),
            => throw new AuthErr('Token is Expired'),

            $token->hasBeenIssuedBy(Env::getJWT()->issuer)
            => throw new AuthErr('Token has an unexpected Issuer'),

            $validator->validate($token, new PermittedFor($_SERVER['SERVER_NAME']))
            => throw new AuthErr('Invalid token audience'),

            self::validatePowers($tokenArray['powers'], $needPowers)
            => throw new AuthErr('powers', 'Нет доступа', 403),

            default => true
        };
    }

    private static function getKey(): InMemory
    {
        $tokenSecret = Env::getJWT()->key;
        return InMemory::plainText($tokenSecret);
    }

    private function buildDatetime(): void
    {
        $this->iat = new DateTimeImmutable($this->createdAt);
        $this->nbf = $this->iat->modify('-1 minute');
        $this->exp = $this->iat->modify($this->expireDuration);
    }

    private static function isExpired(UnencryptedToken $token, bool $ignoreExpire): bool
    {
        $token->claims();
        //printr($token->claims());
        //var_dump($token->isExpired(new DateTimeImmutable()));
        return $token->isExpired(new DateTimeImmutable()) && !$ignoreExpire;
    }

    private static function validatePowers(array $tokenPowers, array $needPowers = []): bool
    {
        if (empty($needPowers)) return true;
        $intersect = array_intersect($needPowers, $tokenPowers);
        return !!count($intersect);
    }
    
    private static function getPowers(string $jwt)
    {
        return self::toArray($jwt)['powers'] ?? [];
    }

}