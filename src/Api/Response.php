<?php

namespace Symphograph\Bicycle\Api;

use JetBrains\PhpStorm\NoReturn;
use Symphograph\Bicycle\AppStore;
use Symphograph\Bicycle\Env\Env;

class Response
{
    #[NoReturn] public static function error(string $msg, int $statusCode = 500, array $trace = []): void
    {
        self::jsonResponse(['error' => $msg, 'trace' => $trace], $statusCode);
    }

    #[NoReturn] public static function data(array|object $data, string $msg = 'Готово', int $statusCode = 200): void
    {
        $data = ['result'=>$msg,'data' => $data, 'warnings' => AppStore::getWarnings()];
        self::jsonResponse($data, $statusCode);
    }

    #[NoReturn] public static function success(string $msg = 'Готово', int $statusCode = 200): void
    {
        $data = ['result'=>$msg];
        self::jsonResponse($data, $statusCode);
    }

    #[NoReturn] private static function jsonResponse(array|object $data, int $statusCode = 200): void
    {
        //header_remove();
        header("Content-Type: application/json");
        http_response_code($statusCode);

        $buffer = ob_get_clean();
        echo json_encode($data,JSON_UNESCAPED_UNICODE);
        if(!empty($buffer) && Env::isDebugMode()){
            echo $buffer;
        }

        die();
    }
}

