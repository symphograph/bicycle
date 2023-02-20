<?php

namespace Symphograph\Bicycle\Api;

use JetBrains\PhpStorm\NoReturn;

class Response
{
    #[NoReturn] public static function error(string $msg, $statusCode = 500): void
    {
        self::jsonResponse(['error' => $msg], $statusCode);
    }

    #[NoReturn] public static function data(array|object $data, string $msg = 'Готово'): void
    {
        $data = ['result'=>$msg,'data' => $data];
        self::jsonResponse($data);
    }

    #[NoReturn] public static function success(string $msg = 'Готово'): void
    {
        $data = ['result'=>$msg];
        self::jsonResponse($data);
    }

    #[NoReturn] private static function jsonResponse(array|object $data, $statusCode = 200): void
    {
        header_remove();
        header("Content-Type: application/json");
        http_response_code($statusCode);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);
        die();
    }
}