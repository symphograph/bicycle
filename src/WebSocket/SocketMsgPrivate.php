<?php

namespace Symphograph\Bicycle\WebSocket;

readonly class SocketMsgPrivate extends SocketMsg
{
    public function __construct(
        string $type,
        public int    $userId,
        array  $payload = [],
        string $key = "socket-events"
    ) {
        parent::__construct($type, $payload, $key);
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['userId'] = $this->userId;
        return $data;
    }


}