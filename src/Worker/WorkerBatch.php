<?php

namespace Symphograph\Bicycle\Worker;

use Symphograph\Bicycle\Repo\Redis\RedisDB;
use Throwable;

/**
 * Пакет задач: список WorkerTask + опциональная финальная задача,
 * которая попадает в очередь ровно один раз, когда счётчик обнулится.
 *
 * Пакет сам проставляет в каждую задачу свой ключ (attachToBatch)
 * перед push(). Задачи про пакеты не знают — они носят ключ как
 * непрозрачную строку.
 */
final class WorkerBatch
{
    public readonly string $key;

    /** @var WorkerTask[] */
    private array $tasks = [];

    private ?WorkerTask $finalTask = null;

    private function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function create(iterable $tasks, ?WorkerTask $onComplete = null): self
    {
        $batch = new self(bin2hex(random_bytes(8)));
        foreach ($tasks as $task) {
            $batch->tasks[] = $task;
        }
        $batch->finalTask = $onComplete;
        return $batch;
    }

    public static function fromKey(string $key): self
    {
        return new self($key);
    }

    // ---------- Постановка ----------

    public function push(): static
    {
        if (empty($this->tasks)) {
            return $this;
        }

        $ownerName = $this->tasks[0]->name();
        RedisDB::set($this->ownerKey(), $ownerName);
        RedisDB::set(self::currentKey($ownerName), $this->key);

        if ($this->finalTask !== null) {
            RedisDB::set($this->finalKey(), $this->finalTask->toJson());
        }

        RedisDB::setCounter($this->totalKey(), count($this->tasks));
        RedisDB::setCounter($this->counterKey(), count($this->tasks));

        foreach ($this->tasks as $task) {
            $task->attachToBatch($this->key);
            $task->push();
        }

        return $this;
    }

    // ---------- Шаг выполнения ----------

    public function step(): void
    {
        $left = RedisDB::decr($this->counterKey());

        if ($left > 0) {
            return;
        }

        RedisDB::del($this->counterKey());
        RedisDB::del($this->totalKey());
        RedisDB::del($this->ownerKey());
        $this->dispatchFinal();
    }

    // ---------- Состояние ----------

    public function key(): string
    {
        return $this->key;
    }

    public function exists(): bool
    {
        return RedisDB::exists($this->totalKey());
    }

    public function total(): int
    {
        return RedisDB::getCounter($this->totalKey()) ?? 0;
    }

    public function left(): int
    {
        return RedisDB::getCounter($this->counterKey()) ?? 0;
    }

    public function done(): int
    {
        return max(0, $this->total() - $this->left());
    }

    public function isDone(): bool
    {
        return !$this->exists() || $this->left() === 0;
    }

    public function isPending(): bool
    {
        return $this->exists() && $this->left() > 0;
    }

    public function state(): WTBatchState
    {
        return new WTBatchState(
            key:   $this->key,
            total: $this->total(),
            left:  $this->left(),
            alive: $this->exists(),
        );
    }

    public static function currentStateFor(string $taskName): ?WTBatchState
    {
        $key = RedisDB::get(self::currentKey($taskName));
        if (!is_string($key) || $key === '') {
            return null;
        }
        return self::fromKey($key)->state();
    }

    // ---------- Служебные ключи ----------

    public static function currentKey(string $taskName): string
    {
        return 'batch:current:' . $taskName;
    }

    private function counterKey(): string { return 'batch:' . $this->key . ':left'; }
    private function totalKey():   string { return 'batch:' . $this->key . ':total'; }
    private function finalKey():   string { return 'batch:' . $this->key . ':final'; }
    private function ownerKey():   string { return 'batch:' . $this->key . ':owner'; }

    // ---------- Финальная задача ----------

    private function dispatchFinal(): void
    {
        $json = RedisDB::get($this->finalKey());
        RedisDB::del($this->finalKey());

        if (!is_string($json) || $json === '') {
            return;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return;
        }

        /** @var class-string<WorkerTask> $className */
        $className = $data['class'] ?? null;
        if (!is_string($className) || !is_subclass_of($className, WorkerTask::class)) {
            error_log("WorkerBatch: invalid final task class '{$className}'");
            return;
        }

        /** @var WorkerTask $task */
        $task = $className::byArr($data);

        try {
            // Финальная задача запускается напрямую, без execute():
            // счётчик пакета уже удалён, шагать некуда.
            $task->run();
        } catch (Throwable $e) {
            error_log("WorkerBatch: final task '{$className}' failed: {$e->getMessage()}");
        }
    }
}