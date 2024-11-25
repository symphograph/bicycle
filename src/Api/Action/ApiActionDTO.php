<?php

namespace Symphograph\Bicycle\Api\Action;

use Symphograph\Bicycle\DTO\DTOTrait;

class ApiActionDTO {

    use DTOTrait;
    const string tableName = 'apiActions';

    public int    $id;
    public string $method;
    public string $controller;
    public string $createdAt;
    public ?int    $persId;
    public string $postData;

    public static function newInstance(
        string $method,
        string $controller,
        ?int    $persId,
        array $postData
    ): static
    {
        $postData = json_encode($postData);
        $vars = get_defined_vars();
        return static::byBind($vars);
    }
};
