<?php

namespace Symphograph\Bicycle;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\AppErr;
use Throwable;

class FileHelper
{
    public static function FileList(string $dir): array
    {
        $dir = self::addRoot($dir);
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

    public static function addRoot(string $file): string
    {
        if (!str_starts_with($file, '/tmp/') && !str_starts_with($file, '/home/')) {
            $file = ServerEnv::DOCUMENT_ROOT() . '/' . $file;
        }

        return self::removeDoubleSeparators($file);
    }

    public static function removeDoubleSeparators(string $dir): string
    {
        return str_replace('//', '/', $dir);
    }

    public static function folderList(string $dir): array
    {
        //Получает массив с именами папок в директории
        $dir = self::addRoot($dir);
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
        $dir = self::addRoot($dir);
        $dir = self::removeDoubleSeparators($dir);
        return file_exists($dir) && !is_dir($dir);
    }

    /**
     * - Сохраняет файл
     * - Если фала нет, создает его
     * - Если нет директории, создаёт её
     *
     * @param string $dir
     * @param $data
     * @return false|int
     */
    public static function fileForceContents(string $dir, $data): false|int
    {
        $parts = explode('/', $dir);
        $file = array_pop($parts);
        $dir = '';
        $i = 0;

        foreach ($parts as $part) {
            $i++;
            if (empty($part)){
                continue;
            }

            $dir = $i == 1 ? $part : "$dir/$part";

            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }
        return file_put_contents("$dir/$file", $data);
    }

    public static function moveUploaded(string $from, string $to): bool
    {
        if (!self::forceDir($to))
            return false;

        return @move_uploaded_file($from, $to);
    }

    public static function forceDir(string $to, bool $addRoot = false): bool
    {
        if ($addRoot) {
            $to = ServerEnv::DOCUMENT_ROOT() . '/' . $to;
            $to = self::removeDoubleSeparators($to);
        }
        $dir = pathinfo($to, PATHINFO_DIRNAME);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true)) {
                return false;
            }
        }
        return true;
    }

    public static function copy(string $from, string $to): bool
    {
        $from = self::addRoot($from);
        $to = self::addRoot($to);

        if (!file_exists($from))
            return false;

        if (!self::forceDir($to))
            return false;

        return @copy($from, $to);
    }

    public static function delDir($dir): bool
    {
        $dir = self::addRoot($dir);
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

    public static function delAllExtensions(string $fileName, array $exts = ['.jpg', '.png', '.jpeg', '.svg']): void
    {
        foreach ($exts as $ext) {
            self::delete($fileName . $ext);
        }
    }

    public static function delete(string $file): bool
    {
        $file = self::addRoot($file);
        if (file_exists($file) && !is_dir($file)) {
            return @unlink($file);
        }
        return false;
    }

    public static function concatenateFiles($directoryPath, $outputFilePath): void
    {
        $outputFile = fopen($outputFilePath, 'w');

        if ($outputFile === false) {
            throw new AppErr('fopen err','Не удалось открыть выходной файл для записи');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directoryPath)
        );

        foreach ($files as $file) {
            if (!$file->isFile() || $file->getPathname() === $outputFilePath){
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