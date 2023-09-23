<?php

namespace Symphograph\Bicycle;

class AppStore
{
    public array $warnings = [];

    public static function getSelf(): self
    {
        global $AppStore;
        if(!isset($AppStore)){
            $AppStore = new self();
        }
        return $AppStore;
    }

    public static function addWarning(string $msg): void
    {
        $AppStore = self::getSelf();
        $AppStore->warnings[] = $msg;
    }

    public static function getWarnings(): array
    {
        $AppStore = self::getSelf();
        return $AppStore->warnings;
    }
}