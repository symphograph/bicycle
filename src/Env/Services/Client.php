<?php

namespace Symphograph\Bicycle\Env\Services;

use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\EmptyOriginErr;
use Symphograph\Bicycle\Errors\Auth\InvalidOriginErr;
use Symphograph\Bicycle\Token\Token;


class Client
{
    private string $name;
    private string $groupName;
    private string $domain;

    public static function newInstance(string $name, string $groupName, string $domain): static
    {
        $service = new static();
        $service->name = $name;
        $service->groupName = $groupName;
        $service->domain = $domain;
        return $service;
    }

    /**
     * @throws EmptyOriginErr
     * @throws InvalidOriginErr
     */
    public static function byOrigin(string $origin): static
    {


        $domain = pathinfo($origin)['basename'];
        $services = ClientList::byEnv()->getList();
        foreach ($services as $service) {
            if($service->domain === $domain){
                return $service;
            }
        }

        throw new InvalidOriginErr($origin);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }

    public static function authServer(): void
    {
        Env::isServerIp()
            ?:  throw new AccessErr('It is not allowed server');

        $jwt = ServerEnv::HTTP_ACCESSTOKEN();
        Token::validation($jwt);
        $token = (object) Token::toArray($jwt);

        if($token->authType !== 'server'){
            throw new AccessErr('Auth type not allowed');
        }
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

}