<?php

namespace Symphograph\Bicycle;

class AppStorage
{
    /**
     * @var static
     */
    public static AppStorage $self;
    public static array      $warnings = [];

    /**
     * @return static
     */
    public static function getSelf(): static
    {
        if(!isset(static::$self)){
            static::$self = new static();
        }
        return static::$self;
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