<?php

namespace Symphograph\Bicycle\Files;


use Symphograph\Bicycle\Errors\ImgErr;
use Symphograph\Bicycle\Errors\Upload\UploadErr;
use Throwable;

class UploadedImg extends TmpUploadFile
{
    public int    $width  = 0;
    public int    $height = 0;
    public string $ext    = '';
    public int    $bits   = 0;

    public function __construct(array $file)
    {
        parent::__construct($file);
        $this->initExtAndSize();
    }

    private function initExtAndSize(): void
    {
        $imgTypes = [
            0  => 'SVG',
            1  => 'GIF',
            2  => 'JPEG',
            3  => 'PNG',
            4  => 'SWF',
            5  => 'PSD',
            6  => 'BMP',
            7  => 'TIFF_II',
            8  => 'TIFF_MM',
            9  => 'JPC',
            10 => 'JP2',
            11 => 'JPX',
            12 => 'JB2',
            13 => 'SWC',
            14 => 'IFF',
            15 => 'WBMP',
            16 => 'XBM',
            17 => 'ICO',
            18 => 'webp'
        ];

        try {
            $is = getimagesize($this->tmpFullPath);
        } catch (Throwable) {
            throw new UploadErr('invalid format', 'Недопустимый формат изображения.');
        }

        if (!$is) {
            if ($this->isSVG($this->tmpFullPath)) {
                $this->ext = 'svg';
                $this->initSvgDimensions();
                return;
            }
            throw new UploadErr('invalid format', 'Недопустимый формат изображения.');
        }

        if (!key_exists($is[2], $imgTypes)) {
            throw new UploadErr('invalid format', 'Недопустимый формат изображения.');
        }

        $this->width = $is[0];
        $this->height = $is[1];
        $this->ext = strtolower($imgTypes[$is[2]]);
        $this->bits = $is['bits'];
    }

    private function isSVG($fullPath): bool
    {
        if (strtolower(parent::getExtension()) !== 'svg') {
            return false;
        }
        $content = file_get_contents($fullPath);
        return str_contains($content, '<svg');
    }

    public function getExtension(): string
    {
        return $this->ext;
    }

    public function isAspectRatio16x9(): bool
    {
        if ($this->width <= 0 || $this->height <= 0) {
            throw new ImgErr('width & height must be > 0', 'Ширина и высота должны быть больше 0');
        }

        $aspectRatio = $this->width / $this->height;
        return abs($aspectRatio - (16 / 9)) < 0.01;
    }

    private function initSvgDimensions(): void
    {
        if (!file_exists($this->tmpFullPath) || !is_readable($this->tmpFullPath)) {
            return;
        }

        $svg = simplexml_load_file($this->tmpFullPath);
        $attributes = $svg->attributes();
        $width = (int) round((float) $attributes->width);
        $height = (int) round((float) $attributes->height);

        if ($width && $height) {
            $this->width = $width;
            $this->height = $height;
            return;
        }

        if (!isset($attributes->viewBox)) {
            return;
        }

        $viewBox = explode(' ', $attributes->viewBox);
        $this->width = $viewBox[2];
        $this->height = $viewBox[3];
    }
}