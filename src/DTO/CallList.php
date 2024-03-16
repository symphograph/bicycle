<?php

namespace Symphograph\Bicycle\DTO;

class CallList extends AbstractList
{
    use DTOTrait;
    public static function getItemClass(): string
    {
        return CallList::class; // Возвращаем класс элементов списка
    }

    public function beforeDel(){}
    public function afterDel(){}
}