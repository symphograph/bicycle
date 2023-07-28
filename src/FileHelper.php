<?php

namespace Symphograph\Bicycle;

class FileHelper
{
    public static function FileList(string $dir): array
    {
        $dir = self::addRoot($dir);
        if(!file_exists($dir)){
            return [];
        }
        $files = scandir($dir);
        if(!$files)
            return [];
        $skip = ['.', '..'];
        $files2 = [];
        foreach($files as $file)
        {
            if(in_array($file, $skip) or is_dir($dir.'/'.$file))
                continue;
            $files2[] = $file;
        }
        return $files2;
    }

    public static function folderList(string $dir): array
    {
        //Получает массив с именами папок в директории
        $dir = self::addRoot($dir);
        $files = scandir($dir);
        $skip = ['.', '..'];
        $folders = [];
        foreach($files as $file)
        {
            if(!in_array($file, $skip) and is_dir($dir.'/'.$file))
                $folders[] = $file;
        }
        return($folders);
    }

    public static function fileExists(string $dir): bool
    {
        $dir = self::addRoot($dir);
        $dir = self::removeDoubleSeparators($dir);
        return file_exists($dir) && !is_dir($dir);
    }

    /**
     * Сохраняет файл. Если нет дириктории, создаёт её.
     * @param string $dir
     * @param $data
     */
    public static function fileForceContents(string $dir,$data): false|int
    {
        //Сохраняет файл. Если нет дириктории, создаёт её.
        $parts = explode('/', $dir);

        $file = array_pop($parts);
        $dir = '';
        $i=0;
        foreach($parts as $part)
        {$i++;
            if(empty($part))
                continue;

            if($i==1)
                $dir = $part;
            else
                $dir .= "/$part";
            if(!is_dir($dir)) mkdir($dir, 0775, true);
        }
        return file_put_contents("$dir/$file", $data);
    }

    public static function moveUploaded(string $from, string $to) : bool
    {
        if(!self::forceDir($to))
            return false;

        return @move_uploaded_file($from, $to);
    }

    public static function forceDir(string $to, bool $addRoot = false) : bool
    {
        if($addRoot){
            $to = $_SERVER['DOCUMENT_ROOT']. '/' . $to;
            $to = self::removeDoubleSeparators($to);
        }
        $dir = pathinfo($to,PATHINFO_DIRNAME);
        if(!is_dir($dir)){
            if(!mkdir($dir, 0775, true)){
                return false;
            }
        }
        return true;
    }

    public static function copy(string $from,string $to): bool
    {
        $from = self::addRoot($from);
        $to = self::addRoot($to);

        if(!file_exists($from))
            return false;

        if(!self::forceDir($to))
            return false;

        return @copy($from,$to);
    }

    public static function removeDoubleSeparators(string $dir) : string
    {
        return str_replace('//','/',$dir);
    }

    public static function addRoot(string $file): string
    {
        if(!str_starts_with($file,'/tmp/') && !str_starts_with($file,'/home/')){
            $file = $_SERVER['DOCUMENT_ROOT']. '/' . $file;
        }

        return self::removeDoubleSeparators($file);
    }

    public static function delDir($dir): bool
    {
        $dir = self::addRoot($dir);
        try {
            $d = opendir($dir);
        } catch (\Throwable) {
            return false;
        }

        if(!$d) return false;
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

    public static function delete(string $file): bool
    {
        $file = self::addRoot($file);
        if(file_exists($file) && !is_dir($file)){
          return @unlink($file);
        }
        return false;
    }

    public static function delAllExtensions(string $fileName, array $exts = ['.jpg', '.png', '.jpeg', '.svg'])
    {
        foreach ($exts as $ext)
        {
            self::delete($fileName . $ext);
        }
    }
}