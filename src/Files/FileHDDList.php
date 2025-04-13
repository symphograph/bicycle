<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\FileHelper;

class FileHDDList
{
    /**
     * @var FileHDD[]
     */
    protected array $list = [];

    public function __construct(array $list = [])
    {
        $this->list = $list;
    }

    protected static function byPath(string $folderPath): static
    {
        $files = FileHelper::FileListInSegmentedFolders($folderPath);
        $list = [];
        foreach ($files as $fullPath) {
            $list[] = FileHDD::byPath($fullPath);
        }
        return new static($list);
    }

    public static function public(): static
    {
        $folder = Env::getStorageFolder()->public;
        return static::byPath($folder);
    }

    public static function tmp(): static
    {
        $folder = Env::getStorageFolder()->tmp;
        return static::byPath($folder);
    }

    public static function allStorage(): static
    {
        $folder = Env::getStorageFolder()->data;
        return static::byPath($folder);
    }


    /**
     * @return FileHDD[]
     */
    public function getList(): array
    {
        return $this->list;
    }

}