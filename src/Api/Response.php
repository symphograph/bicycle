<?php

namespace Symphograph\Bicycle\Api;

use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\AppStorage;
use Symphograph\Bicycle\DTO\BindTrait;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\Files\FileDoesNotExistsErr;
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

    #[NoReturn] public static function error(string $msg, int $httpStatus = 500, array $trace = []): void
    {
        self::jsonResponse(['error' => $msg, 'trace' => $trace], $httpStatus);
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

    #[NoReturn] private static function jsonResponse(array|object $data, int $httpStatus = 200): void
    {
        //header_remove();
        Handler::warningHandler();
        $data['warnings'] = AppStorage::$warnings;
        header("Content-Type: application/json");
        http_response_code($httpStatus);

        $buffer = ob_get_clean();
        echo json_encode($data,JSON_UNESCAPED_UNICODE);
        if(!empty($buffer) && Env::isDebugMode()){
            echo PHP_EOL.$buffer;
        }

        die();

    }

    public static function download(string $fullPath, ?string $fileName = null): void
    {
        if(empty($fileName)){
            $fileName = basename($fullPath);
        }

        if(!FileHelper::fileExists($fullPath)){
            throw new FileDoesNotExistsErr($fullPath);
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
        //readfile($fullPath);
        $file = fopen($fullPath, 'rb');
        fpassthru($file);
        fclose($file);
        exit;
    }
}

