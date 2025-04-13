<?php

namespace Symphograph\Bicycle\Files;

enum FileExt: string
{
    // Images
    case JPG = 'jpg';
    case PNG = 'png';
    case GIF = 'gif';
    case BMP = 'bmp';
    case WEBP = 'webp';
    case SVG = 'svg';
    case TIF = 'tif';
    case ICO = 'ico';

    // Documents
    case PDF = 'pdf';
    case DOC = 'doc';
    case DOCX = 'docx';
    case XLS = 'xls';
    case XLSX = 'xlsx';
    case CSV = 'csv';
    case RTF = 'rtf';
    case ODT = 'odt';
    case ODS = 'ods';

    // Audio
    case MP3 = 'mp3';
    case OGG = 'ogg';
    case WAV = 'wav';

    // Video
    case MP4 = 'mp4';
    case WEBM = 'webm';
    case AVI = 'avi';
    case MOV = 'mov';

    // Archives
    case ZIP = 'zip';
    case RAR = 'rar';
    case SEVENZ = '7z';
    case TAR = 'tar';
}

