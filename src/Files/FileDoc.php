<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\DTO\ModelTrait;
use Symphograph\Bicycle\Errors\Files\InvalidTypeErr;
use Symphograph\Bicycle\FileHelper;

class FileDoc extends FileDTO
{
    use ModelTrait;

    const string mainFolder   = '/uploads/docs';
    const string publicFolder = 'documents';

    public string $type = 'doc';

    public function getPubFullPath(string $fileName): string
    {
        $relPath = $this->getPubRelPath($fileName);
        return FileHelper::fullPath($relPath, true);
    }

    public function getPubRelPath(string $fileName): string
    {
        $md5Path = FileHelper::getMD5Path($this->md5);
        return self::publicFolder . '/' . $md5Path . '/' . $fileName;
    }

    protected function beforePut(): void
    {
        $this->validate();
    }

    public function validate(): void
    {
        parent::validate();
        if ($this->type !== FileType::Doc->value) {
            throw new InvalidTypeErr(FileType::Doc->value, $this->type);
        }
    }

    protected function afterPut(): void
    {
        $this->id = self::idByPut();
    }

    protected function beforeDel()
    {

    }

    protected function afterDel(): void
    {
        $fullPath = $this->getFullPath();
        FileHelper::delete($fullPath);
    }

}