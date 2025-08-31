<?php

namespace Symphograph\Bicycle\Auth\Session;


use Symphograph\Bicycle\Auth\Agent;
use Symphograph\Bicycle\Auth\ModelCookieTrait;
use Symphograph\Bicycle\DTO\ModelTrait;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Env\Services\Client;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\Errors\Auth\EmptyOriginErr;
use Symphograph\Bicycle\PDO\DB;
use Symphograph\Bicycle\Token\AccessToken;
use Symphograph\Bicycle\Token\SessionToken;
use Throwable;

class Session extends SessionDTO
{
    use ModelTrait;
    use ModelCookieTrait;

    public static function create(int $accountId, int $deviceId): self
    {
        $agent = Agent::getSelf();

        $origin = ServerEnv::HTTP_ORIGIN();
        if(empty($origin)) throw new EmptyOriginErr();

        try {
            $Session = new self();
            $Session->marker = self::createMarker();
            $Session->accountId = $accountId;
            $Session->deviceId = $deviceId;
            $Session->client = Client::byOrigin($origin)->getName();
            $Session->firstIp = ServerEnv::REMOTE_ADDR();
            $Session->lastIp = ServerEnv::REMOTE_ADDR();
            $Session->createdAt = date('Y-m-d H:i:s');
            $Session->visitedAt = $Session->createdAt;

            $Session->putToDB();
            $Session->id = DB::lastId();

            return self::byId($Session->id);
        } catch (Throwable $e) {
            throw new AuthErr($e->getMessage(), 'Не удалось создать сессию');
        }
    }

    /**
     * @throws AuthErr
     * @throws AccessErr
     */
    public static function byAccessToken(): self
    {
        $jwt = AccessToken::byHTTP([]);
        return Session::byMarker($jwt->sessionMark)
            ?? throw new AuthErr('session does not exist');
    }

    /**
     * @throws AccessErr
     * @throws AuthErr
     */
    public static function byJWT(): self
    {
        if (empty($_SERVER['HTTP_SESSIONTOKEN']) || empty($_SERVER['HTTP_ACCESSTOKEN'])) {
            throw new AuthErr('tokens is empty');
        }
        SessionToken::validation(jwt: $_SERVER['HTTP_SESSIONTOKEN']);

        $Session = self::byMarker(SessionToken::marker($_SERVER['HTTP_SESSIONTOKEN']))
            ?? throw new AuthErr('Session does not exist', 'Session does not exist');

        $accessToken = AccessToken::byHTTP(needPowers: [], ignoreExpire: true);
        $accessTokenTime = $accessToken->arr()['iat']->getTimestamp();
        $sessionTokenTime = strtotime($Session->visitedAt);
        $diff = $sessionTokenTime - $accessTokenTime;
        if (abs($diff) > 60 * 15) {
            throw new AuthErr('Invalid tokenTime', 'Invalid tokenTime');
        }

        return $Session;
    }

    public static function byCookie(): self
    {
        if(empty($_COOKIE[self::cookieName])){
            throw new AuthErr('session cook is empty');
        }

        return self::byMarker($_COOKIE[self::cookieName])
            ?? throw new AuthErr('session does not exist');
    }

    public static function delByDevice(int $deviceId): void
    {
        $sql = "DELETE FROM sessions WHERE deviceId = :deviceId";
        $params = ['deviceId' => $deviceId];
        DB::qwe($sql, $params);
    }
}