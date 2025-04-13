<?php

namespace Symphograph\Bicycle\Logs;

use Symphograph\Bicycle\Files\FileDTO;

class ResizerLog
{
    public static function alreadyExists(FileDTO $fileDTO, int $width): void
    {
        $msg = "file $fileDTO->id {$fileDTO->fileName()} $width already exists";
        self::write($msg);
    }

    public static function started(FileDTO $fileDTO): void {
        $msg = "file $fileDTO->id {$fileDTO->fileName()} started sizeList";
        self::write($msg);
    }

    public static function processed(FileDTO $fileDTO, int $width): void {
        $msg = "file $fileDTO->id {$fileDTO->fileName()} $width processed";
        self::write($msg);
    }

    public static function completed(FileDTO $fileDTO, int $width): void {
        $msg = "file $fileDTO->id {$fileDTO->fileName()} $width completed";
        self::write($msg);
    }

    private static function write(string $msg): void
    {
        Log::msg(msg: $msg, logFolder: 'resizer');
    }
}