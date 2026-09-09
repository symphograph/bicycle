<?php

namespace Symphograph\Bicycle\WebSocket;

use Symphograph\Bicycle\Repo\Redis\RedisDB;

readonly class PersonalSocketMsg extends SocketMsg
{
    public function __construct(
        public string $type,
        public int    $userId,
        public array  $payload = [],
        public string $key = "socket-events"
    ) {
        parent::__construct($type, $payload, $key);
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['userId'] = $this->userId;
        return $data;
    }

    public function send(): void
    {
        if($this->userId === 0) return;
        RedisDB::client()->rPush($this->key, $this->toJson());
    }
}