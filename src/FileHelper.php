<?php

namespace Symphograph\Bicycle;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Errors\FileErr;
use Symphograph\Bicycle\Errors\MyErrors;
use Throwable;

class FileHelper
{
    public static function FileList(string $dir): array
    {
        $dir = self::fullPath($dir);
        if (!file_exists($dir)) {
            return [];
        }
        $files = scandir($dir);
        if (!$files)
            return [];
        $skip = ['.', '..'];
        $files2 = [];
        foreach ($files as $file) {
            if (in_array($file, $skip) or is_dir($dir . '/' . $file))
                continue;
            $files2[] = $file;
        }
        return $files2;
    }

    public static function fullPath(string $relOrFullPath): string
    {
        if (!self::isRootPath($relOrFullPath)) {
            $fullPath = ServerEnv::DOCUMENT_ROOT() . '/' . $relOrFullPath;
        } else {
            $fullPath = $relOrFullPath;
        }

        return self::cleanPath($fullPath);
    }

    private static function isRootPath(string $path): bool
    {
        return str_starts_with($path, '/tmp/') || str_starts_with($path, '/home/');
    }

    public static function cleanPath(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path);
        return preg_replace('#/\.\./#', '/', $path);
    }

    public static function removeDoubleSeparators(string $dir): string
    {
        return str_replace('//', '/', $dir);
    }

    public static function folderList(string $dir): array
    {
        //Получает массив с именами папок в директории
        $dir = self::fullPath($dir);
        $files = scandir($dir);
        $skip = ['.', '..'];
        $folders = [];
        foreach ($files as $file) {
            if (!in_array($file, $skip) and is_dir($dir . '/' . $file))
                $folders[] = $file;
        }
        return ($folders);
    }

    public static function fileExists(string $dir): bool
    {
        $dir = self::fullPath($dir);
        $dir = self::cleanPath($dir);
        return file_exists($dir) && !is_dir($dir);
    }

    /**
     * - Сохраняет файл
     * - Если фала нет, создает его
     * - Если нет директории, создаёт её
     *
     * @param string $fullPath
     * @param string $data
     * @param int $permissions
     * @return int
     * @throws MyErrors
     */
    public static function fileForceContents(string $fullPath, string $data, int $permissions = 0775): int
    {
        $cleanPath = self::cleanPath($fullPath);

        $dir = dirname($cleanPath);

        if (!is_dir($dir)) {
            mkdir($dir, $permissions, true)
            or throw new FileErr("error on create dir: $dir", "Не удалось создать папку для файла");
        }

        $bytes = file_put_contents($cleanPath, $data);
        if ($bytes === false) {
            throw new FileErr('error on file_put_contents', "Ошибка при создании файла.");
        }
        return $bytes;
    }

    public static function moveUploaded(string $from, string $to): bool
    {
        self::forceDir($to);
        return @move_uploaded_file($from, $to);
    }

    public static function forceDir(string $to, int $permissions = 0775): void
    {
        $fullPath = self::fullPath($to);

        $dir = pathinfo($fullPath, PATHINFO_DIRNAME);
        if(is_dir($dir)) {
            return;
        }
        mkdir($dir, $permissions, true) or
        throw new FileErr("error on create dir: $dir", "Не удалось создать папку для файла");
    }

    public static function copy(string $from, string $to): bool
    {
        $from = self::fullPath($from);
        $to = self::fullPath($to);

        if (!file_exists($from))
            return false;

        self::forceDir($to);

        return @copy($from, $to);
    }

    public static function delDir($dir): bool
    {
        $dir = self::fullPath($dir);
        try {
            $d = opendir($dir);
        } catch (Throwable) {
            return false;
        }

        if (!$d) return false;
        while (($entry = readdir($d)) !== false) {
            if ($entry != "." && $entry != "..") {
                if (is_dir($dir . "/" . $entry)) {
                    self::delDir($dir . "/" . $entry);
                } else {
                    unlink($dir . "/" . $entry);
                }
            }
        }
        closedir($d);
        return rmdir($dir);
    }

    public static function delAllExtensions(string $fileName, array $exts = ['jpg', 'png', 'jpeg', 'svg']): void
    {
        $exts = self::getExtensionsInAllCases($exts);

        foreach ($exts as $ext) {
            self::delete($fileName . '.' . $ext);
        }
    }

    public static function getExtensionsInAllCases(array $exts = ['jpg', 'png', 'jpeg', 'svg']): array
    {
        $extsLowCase = array_map('strtolower', $exts);
        $extsUpCase = array_map('strtoupper', $exts);
        return array_merge($extsLowCase, $extsUpCase);
    }

    public static function delete(string $file): bool
    {
        $file = self::fullPath($file);
        if (file_exists($file) && !is_dir($file)) {
            return @unlink($file);
        }
        return false;
    }

    public static function concatenateFiles($directoryPath, $outputFilePath): void
    {
        $outputFile = fopen($outputFilePath, 'w');

        if ($outputFile === false) {
            throw new FileErr('fopen err', 'Не удалось открыть выходной файл для записи');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directoryPath)
        );

        foreach ($files as $file) {
            if (!$file->isFile() || $file->getPathname() === $outputFilePath) {
                continue;
            }
            // Получаем полный путь файла и записываем его в выходной файл
            $filePath = $file->getPathname();
            fwrite($outputFile, $filePath . "\n");

            // Считываем содержимое файла и записываем в выходной файл
            $fileContent = file_get_contents($filePath);
            fwrite($outputFile, $fileContent . "\n");

            // Добавляем разделитель
            fwrite($outputFile, "-------------\n");
        }

        fclose($outputFile);
    }
}