<?php

namespace Symphograph\Bicycle\Files;
use Symphograph\Bicycle\DTO\ModelTrait;
use Symphograph\Bicycle\Errors\AppErr;



class FileIMG
{
    public function validate(): void
    {
        if ($this->type !== 'img') {
            throw new AppErr('Type must be img');
        }
    }

}