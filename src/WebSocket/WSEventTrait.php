<?php

namespace Symphograph\Bicycle\WebSocket;

trait WSEventTrait
{
    abstract function prefix(): string;

    public function key(): string
    {
        return "{$this->prefix()}.{$this->name}";
        // таким образом, value можно использовать для чего-то другого
    }
}