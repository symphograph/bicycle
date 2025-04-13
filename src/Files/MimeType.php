<?php

namespace Symphograph\Bicycle\Files;

use finfo;
use Symphograph\Bicycle\Errors\Files\FileTypeNotConsistentErr;

class MimeType
{
    public string $mimeType;
    public FileExt $ext;
    public FileType $type;

    const array mimeTypes = [
        // Изображения
        'image/jpeg'                   => ['ext' => 'jpg', 'type' => 'img'],
        'image/pjpeg'                  => ['ext' => 'jpg', 'type' => 'img'],
        'image/png'                    => ['ext' => 'png', 'type' => 'img'],
        'image/gif'                    => ['ext' => 'gif', 'type' => 'img'],
        'image/bmp'                    => ['ext' => 'bmp', 'type' => 'img'],
        'image/webp'                   => ['ext' => 'webp', 'type' => 'img'],
        'image/svg+xml'                => ['ext' => 'svg', 'type' => 'img'],
        'image/tiff'                   => ['ext' => 'tif', 'type' => 'img'],
        'image/x-icon'                 => ['ext' => 'ico', 'type' => 'img'],
        'image/vnd.microsoft.icon'     => ['ext' => 'ico', 'type' => 'img'],

        // Документы
        'application/pdf'              => ['ext' => 'pdf', 'type' => 'doc'],
        'application/msword'           => ['ext' => 'doc', 'type' => 'doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                       => ['ext' => 'docx', 'type' => 'doc'],
        'application/vnd.ms-excel'     => ['ext' => 'xls', 'type' => 'doc'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                                       => ['ext' => 'xlsx', 'type' => 'doc'],
        'text/csv'                     => ['ext' => 'csv', 'type' => 'doc'],
        'application/rtf'              => ['ext' => 'rtf', 'type' => 'doc'],
        'application/vnd.oasis.opendocument.text'
                                       => ['ext' => 'odt', 'type' => 'doc'],
        'application/vnd.oasis.opendocument.spreadsheet'
                                       => ['ext' => 'ods', 'type' => 'doc'],

        // Аудио
        'audio/mpeg'                   => ['ext' => 'mp3', 'type' => 'audio'],
        'audio/ogg'                    => ['ext' => 'ogg', 'type' => 'audio'],
        'audio/wav'                    => ['ext' => 'wav', 'type' => 'audio'],
        'audio/x-wav'                  => ['ext' => 'wav', 'type' => 'audio'],

        // Видео
        'video/mp4'                    => ['ext' => 'mp4', 'type' => 'video'],
        'video/webm'                   => ['ext' => 'webm', 'type' => 'video'],
        'video/x-msvideo'              => ['ext' => 'avi', 'type' => 'video'],
        'video/quicktime'              => ['ext' => 'mov', 'type' => 'video'],

        // Архивы
        'application/zip'              => ['ext' => 'zip', 'type' => 'archive'],
        'application/x-rar-compressed' => ['ext' => 'rar', 'type' => 'archive'],
        'application/x-7z-compressed'  => ['ext' => '7z', 'type' => 'archive'],
        'application/x-tar'            => ['ext' => 'tar', 'type' => 'archive'],
    ];

    private function __construct(string $mimeType, FileExt $ext, FileType $type)
    {
        $this->mimeType = $mimeType;
        $this->ext = $ext;
        $this->type = $type;
    }

    /**
     * @throws FileTypeNotConsistentErr
     */
    public static function byMimeType(string $mimeType): ?self
    {
        if (!self::mimeTypes[$mimeType]) return null;

        $ext  = self::mimeTypes[$mimeType]['ext'];
        $type = self::mimeTypes[$mimeType]['type'];

        $ext = FileExt::from($ext);
        $type = FileType::from($type);

        if(!self::isConsistent($type, $ext)) throw new FileTypeNotConsistentErr($type->value, $ext->value);

        return new self($mimeType, $ext, $type);
    }

    /**
     * @throws FileTypeNotConsistentErr
     */
    public static function byFile(string $fullPath): ?self {
        $mimeType = new finfo(FILEINFO_MIME_TYPE)->file($fullPath);
        return self::byMimeType($mimeType);
    }

    public static function isConsistent(FileType $type, FileExt $ext): bool
    {
        $fn = fn($info) =>
            $info['ext'] === $ext->value &&
            $info['type'] === $type->value;

        return array_any(self::mimeTypes, $fn);
    }
}