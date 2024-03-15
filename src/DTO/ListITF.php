<?php

namespace Symphograph\Bicycle\DTO;


interface ListITF
{
    function getList(): array;

    public static function getItemClass(): string;
}