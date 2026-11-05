<?php

namespace Symphograph\Bicycle\WebSocket;
use BackedEnum;
interface WSEventITF extends BackedEnum
{
    //function prefix(): string;
    public function key(): string;
}
