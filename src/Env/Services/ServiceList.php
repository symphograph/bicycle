<?php

namespace Symphograph\Bicycle\Env\Services;

use Symphograph\Bicycle\DTO\AbstractList;
use Symphograph\Bicycle\Env\Env;

class ServiceList extends AbstractList
{
    /**
     * @var Service[]
     */
    protected array $list = [];

    public static function getItemClass(): string
    {
        return Service::class;
    }

    public static function byEnv(): static
    {
        $services = Env::getServices();
        $arr = [];
        foreach ($services as $service) {
            $arr[] = Service::newInstance($service->name, $service->domain, $service->location, $service->type);
        }
        return new static($arr);
    }


    /**
     * @return Service[]
     */
    public function getList(): array
    {
        return $this->list;
    }
}