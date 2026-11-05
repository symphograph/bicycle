<?php

namespace Symphograph\Bicycle\WebSocket;

use Symphograph\Bicycle\Repo\Redis\RedisDB;

abstract readonly class SocketMsg implements SocketMsgInterface
{
    public function __construct(
        public WSEventITF $type,
        public array  $payload = [],
        public string $key = "socket-events"
    ) {}

    public static function private(WSEventITF $type, int $userId, array $payload = []): SocketMsgPrivate
    {
        return new SocketMsgPrivate($type, $userId, $payload);
    }

    public static function broadcast(WSEventITF $type, array $payload = []): SocketMsgBCast
    {
        return new SocketMsgBCast($type, $payload);
    }

    /**
     * Преобразует объект в массив для JSON-сериализации.
     * Поле type добавляется в корень, payload разворачивается.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->key(),
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