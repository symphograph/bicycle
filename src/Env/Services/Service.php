<?php

namespace Symphograph\Bicycle\Env\Services;

use Symphograph\Bicycle\Errors\AppErr;

class Service
{
    private string $name;
    private string $domain;
    private string $location;
    private string $type;

    public static function newInstance(
        string $name,
        string $domain,
        string $location,
        string $type
    ): static
    {
        $service = new self();
        $service->name = $name;
        $service->domain = $domain;
        $service->location = $location;
        $service->type = $type;
        return $service;
    }


    public function getUrl(): string
    {
        return "https://$this->domain/$this->location";
    }

    public static function byName(string $name): static
    {
        $services = ServiceList::byEnv()->getList();
        foreach ($services as $service) {
            if($service->name === $name){
                return $service;
            }
        }
        throw new AppErr('Service "' . $name . '" not found');
    }

    //** getters **/--------------------------------------------------------

    public function getName(): string
    {
        return $this->name;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getType(): string
    {
        return $this->type;
    }
}