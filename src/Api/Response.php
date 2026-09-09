<?php

namespace Symphograph\Bicycle\Api;

use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\AppStorage;
use Symphograph\Bicycle\DTO\BindTrait;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\Files\FileNotExistsInPathErr;
use Symphograph\Bicycle\Errors\Handler;
use Symphograph\Bicycle\FileHelper;

class Response
{
    use BindTrait;

    public string $result;
    public string $error;
    public array|object $data;
    public array $warnings;
    public int $httpStatus;

    #[NoReturn] public static function error(
        string $msg,
        int $httpStatus = 500,
        array $trace = [],
        string $type = 'Err',
        array $payload = []
    ): void
    {
        $data = ['type' => $type,'message' => $msg, 'trace' => $trace, 'payload' => $payload];
        self::jsonResponse($data, $httpStatus);
    }

    #[NoReturn] private static function jsonResponse(array|object $data, int $httpStatus = 200): void
    {
        //header_remove();
        Handler::warningHandler();
        $data['warnings'] = AppStorage::$warnings;
        header("Content-Type: application/json");
        http_response_code($httpStatus);

        $buffer = ob_get_clean();

        if(!empty($buffer) && Env::isDev()){
            //echo PHP_EOL.$buffer;
            $data['buffer'] = $buffer;
        }
        echo json_encode($data,JSON_UNESCAPED_UNICODE);

        die();

    }

    #[NoReturn] public static function data(array|object $data, string $msg = 'Готово', int $httpStatus = 200): void
    {
        $data = ['result'=>$msg,'data' => $data];
        self::jsonResponse($data, $httpStatus);
    }

    #[NoReturn] public static function success(string $msg = 'Готово', int $httpStatus = 200): void
    {
        $data = ['result'=>$msg];
        self::jsonResponse($data, $httpStatus);
    }

    public static function download(string $fullPath, ?string $fileName = null): void
    {
        if(empty($fileName)){
            $fileName = basename($fullPath);
        }

        if(!FileHelper::fileExists($fullPath)){
            throw new FileNotExistsInPathErr($fullPath);
        }

        $mimeType = 'image/jpeg';
        header('Content-Type: ' . $mimeType);
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fullPath));

        // Отчистить буфер вывода
        ob_clean();
        flush();

        // Отправить файл пользователю
        $file = fopen($fullPath, 'rb');
        fpassthru($file);
        fclose($file);
        exit;
    }

    public static function zip(string $fullPath, ?string $fileName = null): void
    {
        if (!FileHelper::fileExists($fullPath)) {
            throw new FileNotExistsInPathErr($fullPath);
        }

        if ($fileName === null) {
            $fileName = basename($fullPath);
        }

        $mtime = filemtime($fullPath);
        $size  = filesize($fullPath);
        $etag  = sha1($size . ':' . $mtime);

        // === Conditional request === я не понимаю зачем это надо
        if (
            isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
            trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag
        ) {
            http_response_code(304);
            return;
        }

        if (
            isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
            strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime
        ) {
            http_response_code(304);
            return;
        }

        //ini_set('output_buffering', '0');
        //ini_set('zlib.output_compression', '0');

        if (ob_get_level()) {
            ob_end_clean();
        }

        // === Headers ===
        header_remove();
        header('X-Accel-Buffering: no'); // Для nginx
        header('Content-Type: application/zip');
        header('Cache-Control: public, max-age=61, must-revalidate');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        header('ETag: "' . $etag . '"');
        header('Content-Length: ' . $size);

        //ob_clean();
        //flush();

        //$fp = fopen($fullPath, 'rb');
        //fpassthru($fp);
        //fclose($fp);
        readfile($fullPath);
        exit;
    }
}

