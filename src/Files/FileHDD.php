<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\Files\FileErr;
use Symphograph\Bicycle\Errors\Files\FileHashIsInvalid;
use Symphograph\Bicycle\Errors\Files\FileNotExistsInDBErr;
use Symphograph\Bicycle\Errors\Files\FileNotExistsInPathErr;
use Symphograph\Bicycle\Errors\Files\FileMD5IsInvalid;
use Symphograph\Bicycle\Errors\Files\FileTypeNotConsistentErr;
use Symphograph\Bicycle\Errors\Files\FileTypeUnknownErr;
use Symphograph\Bicycle\Errors\MyErrors;
use Symphograph\Bicycle\FileHelper;
use Symphograph\Bicycle\Helpers\Str;
use Throwable;

class FileHDD
{
    use FileHDDTrait;

    public int $size;
    public string $updatedAt;

    /**
     * @throws FileHashIsInvalid
     * @throws FileNotExistsInPathErr
     * @throws FileMD5IsInvalid
     */
    private function __construct(
        public string   $hash,
        public FileExt  $ext,
        public FileType $type,
        private bool $isTmp
    )
    {
        $this->validate();
        $this->initStat();
    }

    /**
     * @throws FileHashIsInvalid
     * @throws FileNotExistsInPathErr
     * @throws FileMD5IsInvalid
     */
    private function validate(): void
    {
        Str::isValidMD5($this->hash) or throw new FileMD5IsInvalid($this->hash);
        $data = $this->getData();
        $this->hash === self::getHash($data) or throw new FileHashIsInvalid($this->hash);
    }

    private function getData(): string
    {
        $path = $this->isTmp ? self::tmpPath($this->hash) : $this->privatePath();
        try {
            $data = file_get_contents($path);
        } catch (Throwable) {
            throw new FileNotExistsInPathErr($path);
        }
        return $data;
    }

    /**
     * @throws FileHashIsInvalid
     * @throws FileNotExistsInPathErr
     * @throws FileMD5IsInvalid
     */
    public static function byStorage(string $hash, FileExt $ext, FileType $type): FileHDD
    {
        return new FileHDD($hash, $ext, $type, false);
    }

    public function fileName(): string
    {
        $ext = !empty($this->ext->value) ? ".{$this->ext->value}" : '';
        return $this->hash . $ext;
    }

    private function initStat(): void
    {
        $fullPath = $this->isTmp ? self::tmpPath($this->hash) : $this->privatePath();
        try {
            $fp = fopen($fullPath, "r");
        } catch (Throwable $err) {
            throw new FileNotExistsInPathErr($this->privatePath());
        }

        $fstat = fstat($fp);
        $this->size = $fstat["size"];

        $ctime = $fstat['ctime']; // of meta
        $mtime = $fstat['mtime']; // of content

        $this->updatedAt = date('Y-m-d H:i:s', max($ctime, $mtime));

        fclose($fp);
    }

    /**
     * @throws FileErr
     */
    public function moveFromTmp(): void
    {
        $fullPath = $this->privatePath();
        FileHelper::fileForceContents($fullPath,'');
        rename(self::tmpPath($this->hash), $fullPath);
        $this->isTmp = false;
    }

    private static function getHash(string $data): string
    {
        return md5($data . Env::salt());
    }


    /**
     * @throws FileTypeNotConsistentErr
     * @throws FileNotExistsInPathErr
     * @throws FileErr
     * @throws FileHashIsInvalid
     * @throws FileTypeUnknownErr
     * @throws FileMD5IsInvalid
     */
    public static function create(string $data): static
    {
        $hash = self::getHash($data);
        $tmpPath = self::tmpPath($hash);
        FileHelper::fileForceContents($tmpPath, $data);
        return FileHDD::byTmpFolder($hash);
    }


    /**
     * @throws FileTypeNotConsistentErr
     * @throws FileNotExistsInPathErr
     * @throws FileHashIsInvalid
     * @throws FileTypeUnknownErr
     * @throws FileMD5IsInvalid
     */
    public static function byTmpFolder(string $hash): ?static
    {
        $tmpPath = self::tmpPath($hash);
        $mime = MimeType::byFile($tmpPath)
            ?? throw new FileTypeUnknownErr('empty');

        return new FileHDD($hash, $mime->ext, $mime->type, true);
    }

    public function isExistsInTmpFolder(): bool
    {
        $tmpPath = self::tmpPath($this->hash);
        return FileHelper::fileExists($tmpPath);
    }

    private static function tmpPath(string $hash): string
    {
        $tmpFolder = Env::getStorageFolder()->tmp;
        return  FileHelper::fullPath("/$tmpFolder/$hash");
    }

    private function path(string $folder): string
    {
        $type = $this->type->value;
        $md5Folders = FileHelper::getSegmentedFolders($this->hash);
        $fileName = $this->fileName();
        $relPath = "$folder/$type/$md5Folders/$fileName";
        return FileHelper::fullPath($relPath);
    }

}