<?php

namespace Symphograph\Bicycle\WebSocket;

interface SocketMsgInterface
{
    public function toArray(): array;
    public function toJson(): string;
    public function send(): void;
}