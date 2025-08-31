<?php

namespace Symphograph\Bicycle;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\Files\FileErr;
use Symphograph\Bicycle\Errors\MyErrors;
use Symphograph\Bicycle\Helpers\Text;

class FileHelper
{
    /**
     * @return string[]
     */
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
            if (in_array($file, $skip) or is_dir("$dir/$file"))
                continue;
            $files2[] = $file;
        }
        return $files2;
    }

    /**
     * @return string[]
     */
    public static function FileListInSegmentedFolders(string $baseDir): array
    {
        $baseDir = self::fullPath($baseDir);
        if (!file_exists($baseDir) || !is_dir($baseDir)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $files = [];
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $fullPath = $fileInfo->getPathname();
                $relativePath = substr($fullPath, strlen($baseDir) + 1);
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                $files[] = $fullPath;
            }
        }

        return $files;
    }

    public static function fullPath(string $relOrFullPath): string
    {
        if (!self::isRootPath($relOrFullPath)) {
            $root = dirname(ServerEnv::DOCUMENT_ROOT());
            $fullPath = "$root/$relOrFullPath";
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
            if (!in_array($file, $skip) and is_dir("$dir/$file"))
                $folders[] = $file;
        }
        return ($folders);
    }

    public static function fileExists(string $dir): bool
    {
        $dir = self::fullPath($dir);

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
     * @throws FileErr
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
        throw new FileErr("error on create dir: $dir", "Не удалось создать папку");
    }

    public static function symlink(string $target, string $linkPath): void
    {
        self::forceDir($linkPath);
        symlink($target, $linkPath);
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
        if (!file_exists($dir)) {
            return true;
        }

        $RecursDirIterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $mode = RecursiveIteratorIterator::CHILD_FIRST;
        $files = new RecursiveIteratorIterator($RecursDirIterator, $mode);

        foreach ($files as $fileInfo) {
            $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileInfo->getRealPath());
        }

        return rmdir($dir);
    }

    /**
     * Delete file or symlink with empty parent dirs
     */
    public static function deleteAndCleanup(string $filePath): void
    {
        if (file_exists($filePath) || is_link($filePath)) {
            unlink($filePath);
        } else {
            return;
        }

        $currentDir = dirname($filePath);

        for($i = 0; $i < 2; $i++) {
            $files = scandir($currentDir);
            if (count($files) > 2) break;
            rmdir($currentDir);
            $currentDir = dirname($currentDir);
        }
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

    public static function renameToTranslit($fileName, $maxLength = 50): string
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileName = pathinfo($fileName, PATHINFO_FILENAME);

        $cleanFilename = str_replace(['-', '+'], ' ', $fileName);
        $cleanFilename = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleanFilename);
        $cleanFilename = preg_replace('/[\s_]+/u', ' ', $cleanFilename);
        $cleanFilename = trim($cleanFilename);
        $words = preg_split('/\s+/', $cleanFilename);
        $ignoreWords = ['и', 'в', 'на', 'с', 'по', 'из', 'для', 'о', 'не', 'за', 'к', 'от'];

        $filteredWords = array_filter(array_map('mb_strtolower', $words), function ($word) use ($ignoreWords) {
            return !in_array($word, $ignoreWords);
        });


        $newFilenameParts = [];
        $currentLength = 0;

        $lastWord = end($filteredWords);
        reset($filteredWords);


        foreach ($filteredWords as $word) {
            $transliteratedWord = Text::transliterate($word);
            $wordLength = mb_strlen($transliteratedWord);

            if ($currentLength + $wordLength + 1 <= $maxLength || $word === $lastWord) {
                $newFilenameParts[] = ucfirst($transliteratedWord);
                $currentLength += $wordLength + 1;
            }

            if ($currentLength > $maxLength) {
                break;
            }
        }

        $englishFilename = implode('', $newFilenameParts);

        if (mb_strlen($cleanFilename) > $maxLength && empty($newFilenameParts)) {
            $englishFilename = mb_substr($cleanFilename, 0, $maxLength);
        }

        if(!empty($ext)) {
            $englishFilename .= '.' . $ext;
        }
        return $englishFilename;
    }

    public static function getSegmentedFolders(string $hash): string
    {
        $subDir1 = substr($hash, 0, 2);
        $subDir2 = substr($hash, 2, 2);
        return $subDir1 . '/' . $subDir2;
    }
}