<?php

namespace Symphograph\Bicycle\Api\Action;

use Symphograph\Bicycle\DTO\ModelTrait;
use Symphograph\Bicycle\Errors\Auth\AccessErr;
use Symphograph\Bicycle\Errors\Auth\AuthErr;
use Symphograph\Bicycle\Token\AccessToken;

class ApiAction extends ApiActionDTO
{
    use ModelTrait;

    /**
     * @throws AuthErr
     * @throws AccessErr
     */
    public static function newInstance(
        string $method,
        string $controller,
        ?int $persId = null,
        array $postData = []
    ): static {
        $persId = AccessToken::byHTTP([])->userId;
        return parent::newInstance($method, $controller, $persId, $_POST ?? []);
    }

    public static function log(string $method, string $controller): void
    {
        $log =  self::newInstance($method, $controller);
        $log->putToDB();
    }
}