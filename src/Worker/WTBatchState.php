<?php

namespace Symphograph\Bicycle\Worker;

/**
 * Снимок состояния пакета. Чистый DTO: ни Redis, ни побочных
 * эффектов, ни методов мутации. Получается через WorkerTask::curBatch().
 */
final readonly class WTBatchState
{
    public function __construct(
        public string $key,
        public int    $total,
        public int    $left,
        public bool   $alive,
    ) {}

    public function done(): int
    {
        return max(0, $this->total - $this->left);
    }

    public function isDone(): bool
    {
        return !$this->alive || $this->left === 0;
    }

    public function isPending(): bool
    {
        return $this->alive && $this->left > 0;
    }
}