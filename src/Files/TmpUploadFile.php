<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\Errors\Upload\EmptyFilesErr;
use Symphograph\Bicycle\Errors\Upload\UploadErr;
use Symphograph\Bicycle\FileHelper;
use Throwable;

class TmpUploadFile
{
    public string $error;
    public string $tmpFullPath;
    public string $name;
    public int    $size;
    private int   $maxSize = 50000000;

    public function __construct(array $file)
    {
        $this->error = $file['error'] ?? '';
        $this->tmpFullPath = $file['tmp_name'] ?? '';
        $this->name = $file['name'] ?? '';
        $this->size = $file['size'] ?? 0;
        $this->validate();
    }

    public static function byExternal(string $externalUrl): static|false
    {
        try {
            $fileData = file_get_contents($externalUrl);
        } catch (Throwable) {
            return false;
        }

        $fileName = md5($fileData);
        $fullPath = FileHelper::fullPath('/uploadtmp/' . $fileName, false);
        FileHelper::fileForceContents($fullPath, $fileData);
        return static::newInstance($fullPath, $fileName);
    }

    public static function newInstance(string $tmpFullPath, string $name): static
    {
        $file = [];
        $file['tmp_name'] = $tmpFullPath;
        $file['name'] = $name;
        $file['size'] = 0;

        return new static($file);
    }

    public static function getFile(): static
    {
        if(empty($_FILES)){
            throw new EmptyFilesErr();
        }
        $file = $_FILES[array_key_first($_FILES)];
        return new static($file);
    }

    protected function validate(): void
    {
        match (true) {

            !empty($this->error)
            => throw new UploadErr($this->error, 'Не удалось загрузить файл.'),

            empty($this->tmpFullPath)
            => throw new UploadErr('empty tmp_name', 'Не удалось загрузить файл.'),

            $this->tmpFullPath == 'none'
            => throw new UploadErr('tmp_name == none', 'Не удалось загрузить файл.'),

            empty($this->name)
            => throw new UploadErr('empty name', 'Ошибка при загрузке.'),

            //!is_uploaded_file($this->tmpFullPath)
            //=> throw new UploadErr('it is not uploaded file', 'Ошибка при загрузке.'),

            $this->size > $this->maxSize
            => throw new UploadErr('Over size', 'Файл слишком большой.'),

            default => false
        };
    }

    public function getExtension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function saveAs(string $newFullPath): void
    {
        FileHelper::fileForceContents($newFullPath,'');
        rename($this->tmpFullPath, $newFullPath);
    }

    public function getMd5(): string
    {
        return md5_file($this->tmpFullPath);
    }
}