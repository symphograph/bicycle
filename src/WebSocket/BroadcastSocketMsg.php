<?php

namespace Symphograph\Bicycle\WebSocket;

readonly class BroadcastSocketMsg extends SocketMsg
{
    public function __construct(
        string $type,
        array $payload = [],
        string $key = "socket-broadcast-events"
    ) {
        parent::__construct($type, $payload, $key);
    }


}