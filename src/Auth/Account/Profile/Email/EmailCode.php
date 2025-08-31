<?php

namespace Symphograph\Bicycle\Auth\Account\Profile\Email;


use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\Errors\CooldownNotExpiredErr;
use Symphograph\Bicycle\Errors\Email\CodeExpiredErr;
use Symphograph\Bicycle\Errors\TryLimitExceededErr;
use Symphograph\Bicycle\Errors\ValidationErr;
use Symphograph\Bicycle\Helpers\Secure;
use Symphograph\Bicycle\Token\Token;

class EmailCode
{
    const int minsBeforeExpired = 15;
    const int minsCooldown      = 3;

    public string  $email;
    public string  $hash;
    public string  $createdAt;
    public ?string $lastTryAt;
    public int     $tryCount;
    public string  $code;
    public string $fingerPrint;

    public static function create(string $email, string $fingerPrint): EmailCode
    {
        $emailCode = new EmailCode();
        $emailCode->email = $email;
        $emailCode->createdAt = date('Y-m-d H:i:s');
        $emailCode->code = Secure::createShortCode();
        $emailCode->hash = $emailCode->hash($emailCode->code);
        $emailCode->fingerPrint = $fingerPrint;
        return $emailCode;
    }

    private function hash(string $code): string
    {
        return md5($this->email . $code . Env::salt() . strtotime($this->createdAt));
    }

    private function isValid(string $code): bool
    {
        return hash_equals($this->hash, $this->hash($code));
    }

    /**
     * @param string $jwt
     * @param string $code
     * @return void
     * @throws CodeExpiredErr
     * @throws TryLimitExceededErr
     * @throws ValidationErr
     * @throws AccessErr
     * @throws AuthErr
     */
    public static function verifyCode(string $jwt, string $code, string $fingerPrint): void
    {
        Token::validation(jwt: $jwt); // throw err

        $email = self::emailByJWT($jwt);
        $emailCode = EmailCodeRepoDB::getLastActiveCode($email, $fingerPrint)
            ?? throw  new CodeExpiredErr();

        if ($emailCode->tryCount >= 3) {
            throw new TryLimitExceededErr();
        }

        if ($emailCode->isValid($code)) return;

        EmailCodeRepoDB::incrementTryCount($emailCode);
        throw new ValidationErr('Invalid code', 'Не верный код');
    }

    public static function emailByJWT(string $jwt): string
    {
        $tokenArr = Token::toArray($jwt);
        return $tokenArr['email'];
    }


    /**
     * @throws CooldownNotExpiredErr
     */
    public static function ensureCooldown(string $email, string $fingerPrint): void
    {
        // Проверяем последний активный код
        $lastCode = EmailCodeRepoDB::getLastActiveCode($email, $fingerPrint);
        if(empty($lastCode)) return;

        $lastTryTime = strtotime($lastCode->lastTryAt ?? $lastCode->createdAt);
        $blockDuration = EmailCode::minsCooldown * 60; // Блокировка на x минут
        $blockUntil = $lastTryTime + $blockDuration;

        // Если блокировка еще активна
        if (time() < $blockUntil) {
            $remaining = $blockUntil - time();
            $mins = ceil($remaining / 60);
            throw new CooldownNotExpiredErr($mins);
        }
    }
}