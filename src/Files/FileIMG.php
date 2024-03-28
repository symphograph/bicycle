<?php

namespace Symphograph\Bicycle\Files;

use Symphograph\Bicycle\Img\Resizer;
use Imagick;
use Symphograph\Bicycle\DTO\ModelTrait;
use Symphograph\Bicycle\Errors\AppErr;
use Symphograph\Bicycle\Errors\Files\FileProcessErr;
use Symphograph\Bicycle\FileHelper;
use Symphograph\Bicycle\Logs\Log;


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
        Log::msg("file $this->id {$this->nameByMD5()} started", [], 'worker');
        try {
            if ($this->ext === 'svg') {
                $this->svgHandler();
                return;
            }

            $this->source = new Imagick($this->getFullPath());
            $sizes = $this->prepareSizes($sizes);

            $this->updateStatus(FileStatus::Process);
            foreach ($sizes as $width) {
                $this->makeSize($width);
            }
            $this->updateStatus(FileStatus::Completed);
        } catch (\Throwable $err) {
            $this->updateStatus(FileStatus::Failed);
            throw new FileProcessErr($err->getMessage());
        }

    }

    private function prepareSizes(array $sizes): array
    {
        if (empty($sizes)) {
            $sizes = self::defaultSizes;
        }
        $sizes = array_unique($sizes);
        if(in_array(0, $sizes)) {
            unset($sizes[array_search(0, $sizes)]);
        }
        rsort($sizes);
        array_unshift($sizes, 0);
        return $sizes;
    }

    private function svgHandler(): void
    {
        FileHelper::copy($this->getFullPath(), $this->getSizedPath());
        $this->updateStatus(FileStatus::Completed);
        Log::msg("file $this->id {$this->nameByMD5()} is svg. completed", [], 'worker');
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
            Log::msg("file $this->id {$this->nameByMD5()} $width already exists", [], 'worker');
            return;
        }

        if ($this->source->getImageWidth() >= $width && $width > 0) {
            $this->source = Resizer::processResize($this->source, $width);
            Log::msg("file $this->id {$this->nameByMD5()} $width processed", [], 'worker');
        }

        $blob = $this->source->getImageBlob();
        FileHelper::fileForceContents($sizedPath, $blob);
        Log::msg("file $this->id {$this->nameByMD5()} $width saved", [], 'worker');
    }

    public function validate(): void
    {
        parent::validate();
        if ($this->type !== 'img') {
            throw new AppErr('Type must be img');
        }
    }

    protected function beforeDel()
    {
        $this->delSizes();
        parent::delById($this->id);
    }

    public function delSizes()
    {
        foreach (self::defaultSizes as $width) {
            $sizedPath = $this->getSizedPath($width);
            FileHelper::delete($sizedPath);
        }
    }

}