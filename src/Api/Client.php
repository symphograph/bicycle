<?php

namespace Symphograph\Bicycle\Api;


use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\AppErr;

class Client
{
    public static function getNameByOrigin(): string|false
    {
        $clientNames = self::getNameList();
        $originDomain = str_replace('https://', '', ServerEnv::HTTP_ORIGIN());
        if(!isset($clientNames[$originDomain])){
            throw new AppErr('client is empty', 'Клиент не найден');
        }

        return $clientNames[$originDomain];
    }

    public static function getGroupName(string $clientName): string
    {
        $groupName = Env::getClientGroups()[$clientName] ?? false;
        if(empty($groupName)){
            throw new AppErr('clientGroup is empty', 'Клиент не найден');
        }
        return $groupName;
    }

    /**
     * Возвращает ассоциативный массив с именами Backend и Frontend клиентов.
     * ['clientDomain' => 'clientName']
     * @return array
     */
    public static function getNameList(): array
    {
        $frontendDomains = Env::getClientDomains();
        $backendDomains = Env::getAPIDomains();

        $frontendDomains = array_flip($frontendDomains);
        $backendDomains = array_flip($backendDomains);

        return array_merge($frontendDomains, $backendDomains);
    }
}