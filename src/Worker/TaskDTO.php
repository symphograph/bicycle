<?php

namespace Symphograph\Bicycle\Worker;

use Symphograph\Bicycle\DTO\DTOTrait;

class TaskDTO
{
    use DTOTrait;

    const string tableName = 'workerTasks';

    public int $id;
    public string $status;


}