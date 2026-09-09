<?php

namespace Symphograph\Bicycle\Worker;

use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Repo\Redis\RedisDB;
use Symphograph\Bicycle\WebSocket\SocketMsg;
use Symphograph\Bicycle\WebSocket\SocketMsgPrivate;

abstract class WorkerTask
{
    protected const string taskName = 'SomeTask';

    protected ?float $createdAt = null;
    protected ?float $startedAt = null;
    protected ?float $finishedAt = null;
    protected int $priority = 0;

    public function __construct(
        protected readonly int $userId,
        ?int $priority = null
    ) {
        $this->createdAt = microtime(true);
        if ($priority !== null) {
            $this->priority = $priority;
        }
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    protected function toArray(): array
    {
        return [
            'task'      => static::taskName,
            'userId'    => $this->userId,
            'priority'  => $this->priority,
            'createdAt' => $this->createdAt,
        ];
    }

    public static function len(): int
    {
        return RedisDB::client()->zCard(static::redisKey());
    }

    public static function redisKey(): string
    {
        return 'queue:' . static::taskName;
    }

    /**
     * Извлекает JSON-данные задачи с максимальным приоритетом.
     * Возвращает декодированный массив или null.
     */
    protected static function rPopData(): ?array
    {
        $result = RedisDB::zPopMax(static::redisKey());

        if (!$result) {
            return null;
        }

        // $result[0] — уже десериализованный member (например, строка JSON)
        $data = json_decode($result[0], true);
        return $data ?: null;
    }

    /**
     * Помещает задачу в ZSET-очередь с текущим приоритетом.
     */
    public function push(): void
    {
        RedisDB::client()->zAdd(
            static::redisKey(),
            $this->priority,
            $this->toJson()
        );
    }

    /**
     * Восстанавливает общие поля задачи из массива данных.
     * Вызывается в наследниках внутри byArr/fromArray после создания объекта.
     */
    protected function restoreBaseFields(array $data): void
    {
        if (isset($data['createdAt'])) {
            $this->createdAt = (float)$data['createdAt'];
        } else {
            $this->createdAt = microtime(true);
            // Логирование при необходимости
        }

        if (isset($data['priority'])) {
            $this->priority = (int)$data['priority'];
        }
    }

    // Методы уведомлений и длительностей остаются без изменений
    abstract function notifyTake(): void;

    public function notifyError(\Throwable $e, $payload = []): void
    {
        if (!Env::isProd()) {
            $payload['msg'] = $e->getMessage();
            $payload['code'] = $e->getCode();
            $payload['trace'] = $e->getTrace();
        }
        $msg = $this->createMsg('error', $payload);
        $msg->send();
    }

    protected function createMsg(string $suffix, array $payload = []): SocketMsgPrivate
    {
        $type = empty($suffix) ? static::taskName : static::taskName . '_' . $suffix;
        return SocketMsg::private(
            type: $type,
            userId: $this->userId,
            payload: $payload
        );
    }

    public function queDur(): float
    {
        if (!$this->startedAt || !$this->createdAt) {
            return 0.0;
        }
        return $this->startedAt - $this->createdAt;
    }

    public function queDurInt(): int
    {
        return (int)($this->queDur() * 1_000_000);
    }

    public function runDur(): float
    {
        if (!$this->finishedAt || !$this->startedAt) {
            return 0.0;
        }
        return $this->finishedAt - $this->startedAt;
    }

    public function runDurInt(): int
    {
        return (int)($this->runDur() * 1_000_000);
    }

    public function setStartedAt(float $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function setFinishedAt(float $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    protected function curCpu(): int
    {
        $usage = getrusage();
        $mul = 1_000_000;
        $user = $usage['ru_utime.tv_sec'] * $mul + $usage['ru_utime.tv_usec'];
        $system = $usage['ru_stime.tv_sec'] * $mul + $usage['ru_stime.tv_usec'];
        return $user + $system;
    }
}