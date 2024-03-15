<?php

namespace Symphograph\Bicycle\Helpers;

class ArrayHelper
{
    public static function filter(array $Array, string $elName, string|int|float $elValue, int $len = 0): array
    {
        $elements = array_filter($Array, fn($el) => $el->$elName === $elValue);
        if($len === 0) {
            return $elements;
        }

        return $len ? $elements : array_slice($elements, 0, $len);
    }

    public static function listOfProp(array $array,string $propName): array
    {
        $arr = [];
        foreach ($array as $item) {
            $arr[] = $item->$propName;
        }
        return $arr;
    }
}