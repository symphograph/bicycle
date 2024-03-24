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

    /**
     * Sorts an array of object by props
     */
    public static function sortMultiArrayByProp($array, array $args = ['votes' => 'desc']): array
    {
        usort($array, function ($a, $b) use ($args) {
            $res = 0;

            $a = (object)$a;
            $b = (object)$b;

            foreach ($args as $k => $v) {
                $res = $a->$k <=> $b->$k;
                if(!$res) continue;
                if ($v == 'desc') $res = -$res;
                break;
            }

            return $res;
        });

        return $array;
    }
}