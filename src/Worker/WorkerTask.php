<?php

namespace Symphograph\Bicycle\Worker;

use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\MyErrors;
use Symphograph\Bicycle\Helpers\TaskTimeTrait;
use Symphograph\Bicycle\Repo\Redis\RedisDB;
use Symphograph\Bicycle\WebSocket\SocketMsg;
use Symphograph\Bicycle\WebSocket\WSEventITF;
use Throwable;

abstract class WorkerTask
{
    use TaskTimeTrait;

    protected const string taskName = 'SomeTask';

    protected int $priority = 0;

    /**
     * Непрозрачный ключ пакета, к которому принадлежит задача.
     * Проставляется снаружи (WorkerBatch::push), задача его не создаёт
     * и не интерпретирует — просто носит с собой. null — задача одиночная.
     */
    private ?string $batchKey = null;



    public function __construct(
        protected readonly int $userId,
        ?int                   $priority = null,
    )
    {
        $this->setCreatedAt();
        if ($priority !== null) {
            $this->priority = $priority;
        }
    }

    // ---------- Идентификация ----------

    public static function name(): string
    {
        return static::taskName;
    }

    public static function curBatch(): ?WTBatchState
    {
        return WorkerBatch::currentStateFor(static::taskName);
    }

    // ---------- Базовое ----------

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @internal Единственный вызов — WorkerBatch::push().
     * Задача не должна звать это сама.
     */
    final public function attachToBatch(string $batchKey): void
    {
        $this->batchKey = $batchKey;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    protected function toArray(): array
    {
        $data = [
            'task'      => static::taskName,
            'class'     => static::class,
            'userId'    => $this->userId,
            'priority'  => $this->priority,
            'createdAt' => $this->createdAt,
        ];
        if ($this->batchKey !== null) {
            $data['batchKey'] = $this->batchKey;
        }
        return $data;
    }

    public static function len(): int
    {
        return RedisDB::client()->zCard(static::redisKey());
    }

    public static function redisKey(): string
    {
        return 'queue:' . static::taskName;
    }

    public static function wakeupKey(): string
    {
        return static::redisKey() . ':wakeup';
    }

    // ---------- Постановка ----------

    /**
     * Положить одиночную задачу в очередь своего типа.
     * Для постановки в составе пакета см. WorkerBatch::push().
     * Переопределяйте, если у задачи своя механика очереди
     * (dedup, отдельный payload-ключ и т.п.).
     */
    public function push(): void
    {
        $added = RedisDB::client()->zAdd(
            static::redisKey(),
            $this->priority,
            $this->toJson(),
        );

        if ($added) {
            RedisDB::lPush(static::wakeupKey(), '1');
        }
    }

    // ---------- Снятие ----------

    /**
     * Снять одну задачу с очереди своего типа.
     *
     * Базовая реализация: член ZSET — это json задачи.
     * Переопределяйте, если member хранит что-то другое
     * (например, идентификатор, а payload лежит в отдельном ключе —
     * см. ScoreImageWT::rPop).
     */
    public static function rPop(): ?static
    {
        $data = static::rPopData();
        return $data ? static::byArr($data) : null;
    }

    /**
     * Сырой json из ZSET. Деталь реализации базового rPop.
     * RedisDB::zPopMax уже применяет unserializeValue к member'у (MSGPACK),
     * поэтому здесь остаётся только json_decode.
     */
    protected static function rPopData(): ?array
    {
        $result = RedisDB::zPopMax(static::redisKey());
        if (!$result) {
            return null;
        }
        $data = json_decode($result[0], true);
        return is_array($data) ? $data : null;
    }

    abstract public static function byArr(array $data): static;

    // ---------- Восстановление ----------

    protected function restoreBaseFields(array $data): void
    {
        $this->createdAt = $data['createdAt'] ?? self::timeMs();

        if (isset($data['priority'])) {
            $this->priority = $data['priority'];
        }

        if (!empty($data['batchKey'])) {
            $this->batchKey = $data['batchKey'];
        }
    }

    // ---------- Запуск ----------

    /**
     * Точка входа для воркер-демона: оборачивает run() и в finally
     * шагает в пакет, если задача состоит в пакете.
     *
     * Финальные задачи пакета запускаются напрямую через run()
     * (WorkerBatch::dispatchFinal) — там счётчик уже удалён,
     * и шагать некуда, поэтому execute() для них не зовётся.
     */
    final public function execute(): void
    {
        try {
            $this->run();
        } finally {
            if ($this->batchKey !== null) {
                WorkerBatch::fromKey($this->batchKey)->step();
            }
        }
    }

    abstract public function run(): void;

    abstract public function notifyTake(): void;

    // ---------- Уведомления ----------


    /**
     * Каждый воркер обязан объявить своё error-событие.
     * Без реализации класс не скомпилируется.
     */
    abstract protected function errorEvent(): WSEventITF;

    /**
     * Единственная точка отправки WS-сообщения воркером.
     * Наследник ссылается на конкретный case своего WSEvent напрямую.
     */
    final protected function emit(WSEventITF $event, array $payload = []): void
    {
        SocketMsg::private($event, $this->userId, $payload)->send();
    }

    public function notifyError(\Throwable $e, $payload = []): void
    {
        $payload['error'] = static::errorPayload($e);
        $this->emit($this->errorEvent(), $payload);
    }

    private function errorPayload(Throwable $e): array
    {
        $isDev = !Env::isProd();

        $message = $e instanceof MyErrors
            ? $e->getResponseMsg()
            : ($isDev ? $e->getMessage() : 'Internal error');

        $data = [
            'message' => $message,
            'type'    => $e instanceof MyErrors ? $e->getType() : $e::class,
        ];
        if ($isDev) {
            $data['trace'] = $e->getTrace();
        }
        return $data;
    }

}