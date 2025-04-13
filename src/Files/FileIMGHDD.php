<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\Errors\Files\FileTypeInvalidErr;
use Symphograph\Bicycle\FileHelper;

class FileIMGHDD
{
    use FileHDDTrait;

    public function __construct(
        public FileHDD $fileHDD,
        public int $width = 0
    )
    {
        if($fileHDD->type->value !== FileType::Img->value) {
            throw new FileTypeInvalidErr(FileType::Img->value, $fileHDD->type->value);
        }
    }

    private function path(string $folder): string
    {
        $hash = $this->fileHDD->hash;
        $ext = $this->fileHDD->ext->value;
        $width = $this->width;
        $fileName = ($width === 0)
            ? "$hash.$ext"
            : "{$hash}_$this->width.$ext";

        $segmentedFolders = FileHelper::getSegmentedFolders($hash);
        $relPath = "$folder/img/$segmentedFolders/$fileName";
        return FileHelper::fullPath($relPath);
    }
}