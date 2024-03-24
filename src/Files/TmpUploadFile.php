<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\Errors\Upload\EmptyFilesErr;
use Symphograph\Bicycle\Errors\Upload\UploadErr;
use Symphograph\Bicycle\FileHelper;

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

    public static function getFile(): static
    {
        if(empty($_FILES)){
            throw new EmptyFilesErr();
        }
        $file = array_shift($_FILES);
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

            !is_uploaded_file($this->tmpFullPath)
            => throw new UploadErr('empty name', 'Ошибка при загрузке.'),

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
        FileHelper::moveUploaded($this->tmpFullPath, $newFullPath);
    }

    public function getMd5(): string
    {
        return md5_file($this->tmpFullPath);
    }
}