<?php

namespace Symphograph\Bicycle\Env\Services;

use Symphograph\Bicycle\DTO\AbstractList;
use Symphograph\Bicycle\Env\Env;

class ClientList extends AbstractList
{
    /**
     * @var Client[]
     */
    protected array $list = [];

    public static function getItemClass(): string
    {
        return Client::class;
    }

    public static function byEnv(): static
    {
        $clients = Env::getClients();
        $arr = [];
        foreach ($clients as $client) {
            $arr[] = Client::newInstance($client->name, $client->groupName, $client->domain);
        }
        return new static($arr);
    }

    /**
     * @return Client[]
     */
    public function getList(): array
    {
        return $this->list;
    }
}