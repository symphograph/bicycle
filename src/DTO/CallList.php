<?php

namespace Symphograph\Bicycle\DTO;

class CallList extends AbstractList
{
    public static function getItemClass(): string
    {
        return CallList::class; // Возвращаем класс элементов списка
    }
}