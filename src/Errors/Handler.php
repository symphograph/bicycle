<?php

namespace Symphograph\Bicycle\Errors;

use ErrorException;
use ReflectionClass;
use Symphograph\Bicycle\Api\Response;
use Symphograph\Bicycle\Env\Config;
use Symphograph\Bicycle\Logs\ErrorLog;
use Symphograph\Bicycle\Logs\Log;
use Throwable;

class Handler
{
    public static function regHandlers(): void
    {
        $selfClass = new self();
        set_error_handler([$selfClass, 'myErrorHandler']);
        set_exception_handler([$selfClass, 'myExceptionHandler']);
        register_shutdown_function([$selfClass, 'myShutdownHandler']);
    }

    public function myShutdownHandler(): void
    {
        $error = error_get_last();
        if ($error !== null) {
            $e = new ErrorException(
                $error['message'], 0, $error['type'], $error['file'], $error['line']
            );
            self::myExceptionHandler($e);
        }
    }

    /**
     * @throws ErrorException
     */
    public static function myErrorHandler($level, $message, $file = '', $line = 0)
    {
        throw new ErrorException($message, 0, $level, $file, $line);
    }

    public static function myExceptionHandler(Throwable $err): void
    {
        ini_set("error_log", Log::createLogPath('/logs/phpErrors/'));
        error_log($err);

        $httpStatus = self::getHttpStatus($err);
        ErrorLog::writeToLog($err);
        if (Config::isApi()) {
            Response::error(self::getErrorMsg($err), $httpStatus);
        }

        http_response_code($httpStatus);
        if (ini_get('display_errors')) {
            echo $err;
            return;
        }
        echo "<h1>Произошла чудовищная ошибка сервера</h1>
          Мы уже работаем над её исправлением.<br>";
    }

    protected static function getErrorMsg(Throwable $err): string
    {
        if (ini_get('display_errors')) {
            return $err->getMessage();
        }
        $reflectClass = new ReflectionClass($err::class);
        if ($reflectClass->hasMethod('getResponseMsg')) {
            return $err->getResponseMsg();
        }
        return '';
    }

    protected static function getHttpStatus(Throwable $err): int
    {
        $reflectClass = new ReflectionClass($err::class);
        if ($reflectClass->hasMethod('getHttpStatus')) {
            return $err->getHttpStatus();
        }
        return 500;
    }
}