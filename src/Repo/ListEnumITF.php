<?php

namespace Symphograph\Bicycle\Repo;

use BackedEnum;
use Symphograph\Bicycle\Repo\Redis\RedisTTL;

interface ListEnumITF extends BackedEnum
{
    public string $value { get; }

    public function key(array $params = []): string;
    public function pattern(): string;
    public function method(): string;
    /** @return string[] */
    public function propNames(): array;
    public function ttl(): RedisTTL;
}