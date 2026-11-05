<?php

namespace Symphograph\Bicycle\Helpers;

trait CpuTrait
{
    protected int  $cpuBefore;
    protected int  $cpuAfter;

    public function cpuSpent(): int
    {
        return $this->cpuAfter - $this->cpuBefore;
    }

    public function setCpuAfter(): void
    {
        $this->cpuAfter = CpuTracker::cur();
    }

    public function setCpuBefore(): void
    {
        $this->cpuBefore = CpuTracker::cur();
    }
}