<?php

namespace Symphograph\Bicycle\Helpers;

use Symphograph\Bicycle\Helpers\DTime\DTF;

trait TaskTimeTrait
{
    public const int msMul = 1_000_000;

    protected int  $createdAt;
    protected int $startedAt;
    protected int $finishedAt;

    public function queDur(): int
    {
        return $this->startedAt - $this->createdAt;
    }

    public function runDur(): int
    {
        return $this->finishedAt - $this->startedAt;
    }

    public function setCreatedAt(): void
    {
        $this->createdAt = self::timeMicro();
    }

    public function setStartedAt(): void
    {
        $this->startedAt = self::timeMicro();
    }

    public function setFinishedAt(): void
    {
        $this->finishedAt = self::timeMicro();
    }

    public static function timeMicro(): int
    {
        return (int) (microtime(true) * self::msMul);
    }

    public function finishedAtFmt(): string
    {
        $dt = DTF::fromMicro($this->finishedAt);
        return DTF::dtSqlMicro($dt);
    }
}