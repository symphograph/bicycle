<?php

namespace Symphograph\Bicycle\Img;

use Imagick;
use ImagickPixel;
use Symphograph\Bicycle\FileHelper;

class ResizeProcess
{
    public static function run(Imagick $image, int $width): Imagick
    {
        // $image = self::removePNGBackground($image);
        // $image->setImageFormat("jpeg") or throw new ImgErr();
        $image->stripimage();

        $resolution = self::getResolution($width);
        $image->setImageResolution($resolution, $resolution);
        $image->resampleImage($resolution, $resolution, Imagick::FILTER_LANCZOS, 1);
        $image->resizeImage($width, 0, 0, 1);
        return $image;
    }

    private static function getResolution(int $width): int
    {
        return $width < 1080 ? 72 : 96;
    }

    private static function removePNGBackground(Imagick $image): Imagick
    {
        $data = $image->identifyImage();
        if ($data['mimetype'] !== 'image/png') return $image;

        $image->setBackgroundColor(new ImagickPixel('transparent'));
        return $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
    }
}