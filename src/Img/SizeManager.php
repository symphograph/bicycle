<?php

namespace Symphograph\Bicycle\Img;

use Imagick;
use Symphograph\Bicycle\Errors\Files\FileProcessErr;
use Symphograph\Bicycle\FileHelper;
use Symphograph\Bicycle\Files\FileHDD;
use Symphograph\Bicycle\Files\FileIMGHDD;
use Symphograph\Bicycle\Files\FileManager;
use Symphograph\Bicycle\Files\FileStatus;
use Symphograph\Bicycle\Logs\ResizerLog;
use Symphograph\Bicycle\PDO\DB;
use Throwable;

class SizeManager
{
    const array  defaultSizes = [0, 1920, 1080, 640, 480, 320, 260, 100, 50];
    private Imagick $source;


    public function __construct(private readonly FileManager $file){}

    public function run(array $sizes = [], bool $isForce = false): void
    {
        $fileDTO = $this->file->fileDTO;
        $fileHDD = $this->file->fileHDD;
        ResizerLog::started($fileDTO);
        try {

            if ($fileDTO->ext === 'svg') {
                $this->svgHandler();
                return;
            }

            $this->source = new Imagick($fileHDD->privatePath());
            $sizes = $this->prepareSizes($sizes);

            $this->file->fileDTO->updateStatus(FileStatus::Process);
            foreach ($sizes as $width) {
                if($width === 0) continue;
                $this->makeSize($width, $isForce);
            }
            $fileDTO->updateStatus(FileStatus::Completed);

        } catch (Throwable $err) {
            DB::safeRollback();
            $fileDTO->updateStatus(FileStatus::Failed);
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
        $fileDTO = $this->file->fileDTO;
        $fileDTO->updateStatus(FileStatus::Completed);
        ResizerLog::completed($fileDTO, 0);
    }

    private function makeSize(int $width, bool $isForce = false): void
    {
        $fileDTO = $this->file->fileDTO;
        $fileHDD = $this->file->fileHDD;
        $sizedPath = new FileIMGHDD($fileHDD, $width)->privatePath();

        if (FileHelper::fileExists($sizedPath) && !$isForce) {
            ResizerLog::alreadyExists($fileDTO, $width);
            if($fileHDD->isPublic()) {
                new FileIMGHDD($fileHDD, $width)->setAsPublic();
            }
            return;
        }

        if ($this->source->getImageWidth() >= $width && $width > 0) {
            $this->source = ResizeProcess::run($this->source, $width);
            ResizerLog::processed($fileDTO, $width);
        }

        $blob = $this->source->getImageBlob();
        FileHelper::fileForceContents($sizedPath, $blob);
        if($fileHDD->isPublic()) {
            new FileIMGHDD($fileHDD, $width)->setAsPublic();
        }
        ResizerLog::completed($fileDTO, $width);
    }

    public static function delSizes(FileHDD $fileHDD): void
    {
        foreach (self::defaultSizes as $width) {
            if($width === 0) continue;
            new FileIMGHDD($fileHDD, $width)->delete();
        }
    }

    public static function setAsPublic(FileHDD $fileHDD): void
    {
        foreach (self::defaultSizes as $width) {
            new FileIMGHDD($fileHDD, $width)->setAsPublic();
        }
    }

    public static function setAsPrivate(FileHDD $fileHDD): void
    {
        foreach (self::defaultSizes as $width) {
            new FileIMGHDD($fileHDD, $width)->setAsPrivate();
        }
    }
}