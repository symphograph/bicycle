<?php

namespace Symphograph\Bicycle;

class AppStorage
{
    public static AppStorage $self;
    public static array $warnings = [];

    public static function getSelf(): self
    {
        if(!isset(self::$self)){
            self::$self = new self();
        }
        return self::$self;
    }

    public static function addWarning(string $msg): void
    {
        self::$warnings[] = $msg;
    }

    public static function getWarnings(): array
    {
        //$AppStorage = self::getSelf();
        return self::$warnings;
    }
}