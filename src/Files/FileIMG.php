<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\Img\Resizer;
use Imagick;
use Symphograph\Bicycle\DTO\ModelTrait;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Errors\Files\FileProcessErr;
use Symphograph\Bicycle\FileHelper;


class FileIMG extends FileDTO
{
    use ModelTrait;

    const string mainFolder   = '/uploads/img';
    const string sizedFolder  = 'img/sized';
    const array  defaultSizes = [0, 1920, 1080, 640, 480, 320, 260, 100, 50];

    public string  $type = 'img';
    public Imagick $source;


    public function makeSizes(array $sizes = []): void
    {
        try {
            if ($this->ext === 'svg') {
                $this->svgHandler();
                return;
            }
            $this->source = new Imagick($this->getFullPath());
            if (empty($sizes)) {
                $sizes = self::defaultSizes;
            }
            $this->updateStatus(FileStatus::Process);
            foreach ($sizes as $width) {
                $this->makeSize($width);
            }
            $this->updateStatus(FileStatus::Completed);
        } catch (\Throwable) {
            $this->updateStatus(FileStatus::Failed);
            throw new FileProcessErr($this->id);
        }

    }

    private function svgHandler(): void
    {
        FileHelper::copy($this->getFullPath(), $this->getSizedPath());
        $this->updateStatus(FileStatus::Completed);
    }

    public function getSizedPath(int $width = 0): string
    {
        if ($width === 0) {
            $width = 'original';
        }
        $md5Path = FileHelper::getMD5Path($this->md5);
        $relPath = self::sizedFolder . '/' . $width . '/' . $md5Path . '/' . $this->nameByMD5();
        return FileHelper::fullPath($relPath, true);
    }

    private function makeSize(int $width): void
    {
        $sizedPath = $this->getSizedPath($width);
        if (FileHelper::fileExists($sizedPath)) {
            return;
        }

        if ($this->source->getImageWidth() >= $width && $width > 0) {
            $this->source = Resizer::processResize($this->source, $width);
        }

        $blob = $this->source->getImageBlob();
        FileHelper::fileForceContents($sizedPath, $blob);


    }

    public function validate(): void
    {
        parent::validate();
        if ($this->type !== 'img') {
            throw new AppErr('Type must be img');
        }
    }

}