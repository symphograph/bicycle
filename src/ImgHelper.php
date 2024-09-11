<?php

namespace Symphograph\Bicycle;

class ImgHelper
{
    public static function getExtension(string $filePath): false|string
    {
        return match (exif_imagetype($filePath)) {
            1 => 'gif',
            2 => 'jpg',
            3 => 'png',
            4 => 'swf',
            5 => 'psd',
            6 => 'bmp',
            7, 8 => 'tiff',
            9 => 'jpc',
            10 => 'jp2',
            11 => 'jpx',
            12 => 'jb2',
            13 => 'swc',
            14 => 'iff',
            15 => 'wbmp',
            16 => 'xbm',
            17 => 'ico',
            default => false,
        };
    }
}