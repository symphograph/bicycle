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
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Throwable;


class Token
{
    public string $jwt;

    public string             $jti; // (JWT id) идентификатор токена
    public string             $iss; // (issuer) издатель токена
    private DateTimeImmutable $iat; // (issued at) время создания токена
    private DateTimeImmutable $exp; // (expire time) срок действия токена
    private DateTimeImmutable $nbf; // (not before) срок, до которого токен не действителен

    public function __construct(
        public ?string $sub = 'auth', // (subject) "тема", назначение токена
        public ?int    $uid = null,
        public ?array  $aud = [], // (audience) аудитория, получатели токена
        public string  $createdAt = 'now',
        public string  $expireDuration = '+1 hour',
        string         $iss = '', // (issuer) издатель токена
        public array   $claims = [],
    )
    {

        try {
            $this->jti = bin2hex(random_bytes(32));
            $this->iss = !empty($iss) ? $iss : ServerEnv::SERVER_NAME();
            self::buildDatetime();
            self::initJWT();
        } catch (Throwable $e) {
            throw new AuthErr($e->getMessage(), 'Ошибка генерации токена', 500);
        }

    }

    /**
     * @throws \DateMalformedStringException
     */
    private function buildDatetime(): void
    {
        $this->iat = new DateTimeImmutable($this->createdAt);
        $this->nbf = $this->iat->modify('-1 minute');
        $this->exp = $this->iat->modify($this->expireDuration);
    }

    private function initJWT(): void
    {

        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $tokenBuilder = $tokenBuilder
            ->issuedBy($this->iss) // iss (issuer) издатель токена
            ->permittedFor(...$this->aud) // aud (audience) аудитория, получатели токена
            ->identifiedBy($this->jti) // jti (JWT id) идентификатор токена
            ->relatedTo($this->sub ?? 'auth')
            ->issuedAt($this->iat) // iat (issued at) время создания токена
            ->canOnlyBeUsedAfter($this->nbf) // nbf (not before) срок, до которого токен не действителен
            ->expiresAt($this->exp); // exp (expire time) срок действия токена

        foreach ($this->claims as $claimName => $claimValue) {
            $tokenBuilder = $tokenBuilder->withClaim($claimName, $claimValue);
        }
        $tokenBuilder = $tokenBuilder->withClaim('uid', $this->uid);
        $Token = $tokenBuilder->getToken(new Sha256(), self::getKey());

        $this->jwt = $Token->toString();
    }

    private static function getKey(): InMemory
    {
        $tokenSecret = Env::getJWT()->key;
        return InMemory::plainText($tokenSecret);
    }

    public static function validation(
        string  $jwt,
        ?string $expectedSubject = null,
        bool    $ignoreExpire = false
    ): void
    {
        $token = self::parse($jwt);
        $validator = new Validator();

        match (false) {
            $validator->validate($token, new SignedWith(new Sha256(), self::getKey()))
            => throw new AuthErr('Invalid token key'),

            $validator->validate($token, new RelatedTo($expectedSubject ?? 'auth'))
            => throw new AuthErr('Invalid token subject'),

            !self::isExpired($token, $ignoreExpire),
            => throw new AuthErr('Token is Expired'),

            $token->hasBeenIssuedBy(/*Env::getJWT()->issuer*/ $_SERVER['SERVER_NAME'])
            => throw new AuthErr("Token has an unexpected Issuer: {$_SERVER['SERVER_NAME']}"),

            $validator->validate($token, new PermittedFor(ServerEnv::SERVER_NAME()))
            => throw new AuthErr('Invalid token audience'),

            default => true
        };
    }

    private static function parse(string $jwt): UnencryptedToken
    {
        $parser = new Parser(new JoseEncoder());
        return $parser->parse($jwt);
    }

    public static function toArray(string $jwt): array
    {
        $parser = new Parser(new JoseEncoder());
        $token = $parser->parse($jwt);
        assert($token instanceof UnencryptedToken);
        return $token->claims()->all();
    }

    private static function isExpired(UnencryptedToken $token, bool $ignoreExpire): bool
    {
        $token->claims();
        //printr($token->claims());
        //var_dump($token->isExpired(new DateTimeImmutable()));
        return $token->isExpired(new DateTimeImmutable()) && !$ignoreExpire;
    }





}