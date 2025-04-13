<?php

namespace Symphograph\Bicycle\Auth\User;

use Symphograph\Bicycle\Auth\Account\Account;
use Symphograph\Bicycle\Auth\ModelCookieTrait;
use Symphograph\Bicycle\DTO\ModelTrait;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\PDO\DB;
use Symphograph\Bicycle\Token\AccessToken;
use Throwable;

class User extends UserDTO
{
    use ModelTrait;
    use ModelCookieTrait;

    public static function create(): self
    {
        try {
            $User = new User();
            $User->createdAt = date('Y-m-d H:i:s');
            $User->visitedAt = $User->createdAt;
            $User->putToDB();
            $User->id = DB::lastId();
            return self::byId($User->id);
        } catch (Throwable $e) {
            self::unsetCookie();
            throw new AuthErr($e->getMessage(), 'Ошибка создания пользователя', 401);
        }
    }

    public static function byAccount(int $accountId): ?self
    {
        $account = Account::byId($accountId);
        if(empty($account->userId)){
            return null;
        }
        return self::byId($account->userId);
    }

    public static function auth(array $allowedPowers = []): void
    {
        AccessToken::validation(ServerEnv::HTTP_ACCESSTOKEN(), $allowedPowers);
    }

    public static function byJWT(): ?self
    {
        if(empty(ServerEnv::HTTP_ACCESSTOKEN())) return null;
        $id = AccessTokenData::userId();
        return self::byId($id);
    }

}