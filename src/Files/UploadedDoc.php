<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\Errors\Upload\UploadErr;
use Symphograph\Bicycle\FileHelper;

class UploadedDoc extends TmpUploadFile
{
    const array allowedExtensions = ['doc', 'docx', 'xls', 'xlsx', 'csv', 'pdf'];
    const string folderPath = '/documents';
    const string prefix = 'USSO';

    protected function validate(): void
    {
        parent::validate();

        if(!$this->isDocument()) {
            throw new UploadErr('invalid format', 'Недопустимый формат документа.');
        }
    }

    private function isDocument(): bool
    {
        $ext = $this->getExtension();
        return in_array($ext, self::allowedExtensions);
    }

    public static function getRelPath(string $fileName): string
    {
        return UploadedDoc::folderPath . '/' . $fileName;
    }

    public static function getFullPath(string $fileName): string
    {
        $relPath = self::getRelPath($fileName);
        return FileHelper::fullPath($relPath);
    }

}