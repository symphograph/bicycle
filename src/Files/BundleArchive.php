<?php

namespace Symphograph\Bicycle\Files;

use SplFileInfo;
use RuntimeException;
use Symphograph\Bicycle\FileHelper;

/**
 * Один zip-архив, который периодически пересобирается.
 *
 * Версия архива — mtime файла. Меняется при каждом rebuild,
 * клиент использует её только для сравнения «моё / не моё».
 *
 * Сборка атомарна: пишем во временный файл, потом rename.
 * Параллельные чтения не могут увидеть недописанный файл.
 * Сериализация сборок — flock на sidecar-локе.
 *
 * Формат имени: {$prefix}.zip
 */
class BundleArchive
{
    public function __construct(
        private readonly string $prefix,
        private readonly string $dir = 'bandles',
    ) {}

    /**
     * mtime текущего файла. 0, если файла нет.
     */
    public function currentVersion(): int
    {
        $full = $this->fullPath();
        if (!file_exists($full)) return 0;

        clearstatcache(true, $full);
        return filemtime($full);
    }

    /**
     * Актуальный архив. Если файла нет — собирает.
     */
    public function get(callable $build): SplFileInfo
    {
        $full = $this->fullPath();

        if (file_exists($full)) {
            return new SplFileInfo($full);
        }

        $this->rebuild($build);
        return new SplFileInfo($full);
    }

    /**
     * Собирает новую версию. Атомарно подменяет файл.
     * Возвращает новую версию (mtime).
     *
     * @param callable(string $fullPath): void $build
     */
    public function rebuild(callable $build): int
    {
        $full = $this->fullPath();
        FileHelper::forceDir($this->relPath());

        $tmp  = $full . '.tmp';
        $lock = $full . '.lock';

        $fp = fopen($lock, 'c');
        if ($fp === false || !flock($fp, LOCK_EX)) {
            throw new RuntimeException("Cannot lock archive: $lock");
        }

        try {
            $build($tmp);
            rename($tmp, $full);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
            @unlink($lock);
        }

        clearstatcache(true, $full);
        return filemtime($full);
    }

    public function delete(): void
    {
        @unlink($this->fullPath());
    }

    private function relPath(): string
    {
        return $this->dir . '/' . $this->prefix . '.zip';
    }

    private function fullPath(): string
    {
        return FileHelper::fullPath($this->relPath());
    }
}