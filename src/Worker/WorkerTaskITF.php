<?php

namespace Symphograph\Bicycle\Worker;

interface WorkerTaskITF
{
    public static function fromArray(array $data): static;
}