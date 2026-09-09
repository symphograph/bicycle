<?php
declare(strict_types=1);

namespace Symphograph\Bicycle\WebSocket;

use Symphograph\Bicycle\Env\Env;

class WebSocketConfig
{
    public function __construct(
        private string $channelHost = '0.0.0.0',
        private int    $channelPort = 2206,
        private string $socketHost = '0.0.0.0',
        private int    $socketPort = 2346,
        private int    $maxSendBufferSize = 1024 * 1024 * 2,
        private int    $authTimeoutSeconds = 5,
        private float  $redisPollIntervalSeconds = 0.01,
        private int    $redisQueueWarningThreshold = 100,
        private int    $redisMaxMessagesPerTick = 50,
        private int    $redisQueueCheckIntervalSeconds = 5,
        private string $redisQueueName = 'socket-events',
        private string $channelEventName = 'sendToUser',
        private string $channelClientHost = '127.0.0.1',
        // Новые поля для broadcast
        private string $redisBroadcastQueueName = 'socket-broadcast-events',
        private string $channelBroadcastEventName = 'broadcastToAll'
    )
    {
    }


    /**
     * Создаёт конфигурацию на основе окружения (prod/dev).
     */
    public static function fromEnv(): self
    {
        $isProd = Env::isProd();
        $config = new self();
        return $config
            ->setChannelPort($isProd ? 2206 : 2207)
            ->setSocketPort($isProd ? 2346 : 2347);
    }

    // === Геттеры ===
    public function getChannelHost(): string
    {
        return $this->channelHost;
    }

    public function getChannelPort(): int
    {
        return $this->channelPort;
    }

    public function getSocketHost(): string
    {
        return $this->socketHost;
    }

    public function getSocketPort(): int
    {
        return $this->socketPort;
    }

    public function getMaxSendBufferSize(): int
    {
        return $this->maxSendBufferSize;
    }

    public function getAuthTimeoutSeconds(): int
    {
        return $this->authTimeoutSeconds;
    }

    public function getRedisPollIntervalSeconds(): float
    {
        return $this->redisPollIntervalSeconds;
    }

    public function getRedisQueueWarningThreshold(): int
    {
        return $this->redisQueueWarningThreshold;
    }

    public function getRedisMaxMessagesPerTick(): int
    {
        return $this->redisMaxMessagesPerTick;
    }

    public function getRedisQueueCheckIntervalSeconds(): int
    {
        return $this->redisQueueCheckIntervalSeconds;
    }

    public function getRedisQueueName(): string
    {
        return $this->redisQueueName;
    }

    public function getChannelEventName(): string
    {
        return $this->channelEventName;
    }

    public function getChannelClientHost(): string
    {
        return $this->channelClientHost;
    }

    // Новые геттеры для broadcast
    public function getRedisBroadcastQueueName(): string
    {
        return $this->redisBroadcastQueueName;
    }

    public function getChannelBroadcastEventName(): string
    {
        return $this->channelBroadcastEventName;
    }

    // === Сеттеры (fluent) ===
    public function setChannelHost(string $channelHost): self
    {
        $this->channelHost = $channelHost;
        return $this;
    }

    public function setChannelPort(int $channelPort): self
    {
        $this->channelPort = $channelPort;
        return $this;
    }

    public function setSocketHost(string $socketHost): self
    {
        $this->socketHost = $socketHost;
        return $this;
    }

    public function setSocketPort(int $socketPort): self
    {
        $this->socketPort = $socketPort;
        return $this;
    }

    public function setMaxSendBufferSize(int $maxSendBufferSize): self
    {
        $this->maxSendBufferSize = $maxSendBufferSize;
        return $this;
    }

    public function setAuthTimeoutSeconds(int $authTimeoutSeconds): self
    {
        $this->authTimeoutSeconds = $authTimeoutSeconds;
        return $this;
    }

    public function setRedisPollIntervalSeconds(float $redisPollIntervalSeconds): self
    {
        $this->redisPollIntervalSeconds = $redisPollIntervalSeconds;
        return $this;
    }

    public function setRedisQueueWarningThreshold(int $redisQueueWarningThreshold): self
    {
        $this->redisQueueWarningThreshold = $redisQueueWarningThreshold;
        return $this;
    }

    public function setRedisMaxMessagesPerTick(int $redisMaxMessagesPerTick): self
    {
        $this->redisMaxMessagesPerTick = $redisMaxMessagesPerTick;
        return $this;
    }

    public function setRedisQueueCheckIntervalSeconds(int $redisQueueCheckIntervalSeconds): self
    {
        $this->redisQueueCheckIntervalSeconds = $redisQueueCheckIntervalSeconds;
        return $this;
    }

    public function setRedisQueueName(string $redisQueueName): self
    {
        $this->redisQueueName = $redisQueueName;
        return $this;
    }

    public function setChannelEventName(string $channelEventName): self
    {
        $this->channelEventName = $channelEventName;
        return $this;
    }

    public function setChannelClientHost(string $channelClientHost): self
    {
        $this->channelClientHost = $channelClientHost;
        return $this;
    }

    // Новые сеттеры для broadcast
    public function setRedisBroadcastQueueName(string $redisBroadcastQueueName): self
    {
        $this->redisBroadcastQueueName = $redisBroadcastQueueName;
        return $this;
    }

    public function setChannelBroadcastEventName(string $channelBroadcastEventName): self
    {
        $this->channelBroadcastEventName = $channelBroadcastEventName;
        return $this;
    }
}