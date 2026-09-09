<?php

namespace Symphograph\Bicycle\Repo;

use RuntimeException;

final class RepoRegistry
{
    /** @var array<class-string, RepoITF> */
    private static array $repos = [];

    /** Регистрирует репозиторий по связанному классу */
    public static function register(RepoITF $repo): void
    {
        if($repo::class()::$repo) return;
        self::$repos[$repo::class()] = $repo;
    }

    public static function set(RepoITF $repo): void
    {
        self::$repos[$repo::class()] = $repo;
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @return RepoITF<T>
     */
    public static function get(string $class): RepoITF
    {
        return self::$repos[$class]
            ?? throw new RuntimeException("Repo for $class not registered");
    }
}