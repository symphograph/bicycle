<?php

namespace Symphograph\Bicycle\Debug;

use Symphograph\Bicycle\DTO\AbstractList;

class TestClassList extends AbstractList
{
    /**
     * @var TestClassList[]
     */
    protected array $list = [];

    #[\Override] public static function getItemClass(): string
    {
        return TestClass::class;
    }
}