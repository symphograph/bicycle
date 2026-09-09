<?php

namespace Symphograph\Bicycle\WebSocket;

use Symphograph\Bicycle\Repo\Redis\RedisDB;

abstract readonly class SocketMsg implements SocketMsgInterface
{
    public function __construct(
        public string $type,
        public array  $payload = [],
        public string $key = "socket-events"
    ) {}

    public static function private(string $type, int $userId, array $payload = []): SocketMsgPrivate
    {
        return new SocketMsgPrivate($type, $userId, $payload);
    }

    public static function broadcast(string $type, array $payload = []): BroadcastSocketMsg
    {
        return new BroadcastSocketMsg($type, $payload);
    }

    /**
     * Преобразует объект в массив для JSON-сериализации.
     * Поле type добавляется в корень, payload разворачивается.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            ...$this->payload,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function send(): void
    {
        RedisDB::client()->rPush($this->key, $this->toJson());
    }


}