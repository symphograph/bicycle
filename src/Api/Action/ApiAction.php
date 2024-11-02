<?php

namespace Symphograph\Bicycle\Api\Action;

use Symphograph\Bicycle\DTO\ModelTrait;
use Symphograph\Bicycle\Token\AccessTokenData;

class ApiAction extends ApiActionDTO
{
    use ModelTrait;

    public static function newInstance(
        string $method,
        string $controller,
        ?int $persId = null,
        array $postData = []
    ): static {
        $persId = AccessTokenData::persId();
        return parent::newInstance($method, $controller, $persId, $_POST ?? []);
    }

    public static function log(string $method, string $controller): void
    {
        $log =  self::newInstance($method, $controller);
        $log->putToDB();
    }
}