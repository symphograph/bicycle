<?php

namespace Symphograph\Bicycle\WebSocket;

readonly class SocketMsgBCast extends SocketMsg
{
    public function __construct(
        WSEventITF $type,
        array $payload = [],
        string $key = "socket-broadcast-events"
    ) {
        parent::__construct($type, $payload, $key);
    }


}