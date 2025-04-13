<?php

namespace Symphograph\Bicycle\Auth;

trait ModelCookieTrait
{
    public static function byMarker(string $id): ?self
    {
        $parent = parent::byMarker($id);
        if(!$parent) return null;
        return self::byBind($parent);
    }

}