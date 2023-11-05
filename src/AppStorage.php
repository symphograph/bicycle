<?php

namespace Symphograph\Bicycle;

class AppStorage
{
    public array $warnings = [];

    public static function getSelf(): self
    {
        global $AppStorage;
        if(!isset($AppStorage)){
            $AppStorage = new self();
        }
        return $AppStorage;
    }

    public static function addWarning(string $msg): void
    {
        $AppStorage = self::getSelf();
        $AppStorage->warnings[] = $msg;
    }

    public static function getWarnings(): array
    {
        $AppStorage = self::getSelf();
        return $AppStorage->warnings;
    }
}