<?php

namespace Symphograph\Bicycle\Files;

interface FileITF
{
    function validate(): void;

    function getFullPath(): string;
}