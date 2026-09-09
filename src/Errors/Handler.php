<?php

namespace Symphograph\Bicycle\Errors;

use ErrorException;
use JetBrains\PhpStorm\NoReturn;
use ReflectionClass;
use Symphograph\Bicycle\Api\Response;
use Symphograph\Bicycle\AppStorage;
use Symphograph\Bicycle\Auth\User\UserCpuTracker;
use Symphograph\Bicycle\Env\Config;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Logs\ErrorLog;
use Symphograph\Bicycle\Logs\Log;
use Symphograph\Bicycle\PDO\DB;
use Throwable;

class Handler
{
    public static function regHandlers(): void
    {
        $selfClass = new self();
        set_error_handler([$selfClass, 'myErrorHandler']);
        set_exception_handler([$selfClass, 'myExceptionHandler']);
        register_shutdown_function([$selfClass, 'myShutdownHandler']);

        if(Config::isApi() || Config::isCurl()){
            ob_start();
        }
    }

    public static function warningHandler(): void
    {
        $error = error_get_last();
        if(empty($error)) {
            return;
        }

        if(!Env::isProd() && in_array($error['type'], [E_WARNING, E_NOTICE, E_DEPRECATED])) {
            AppStorage::$warnings[] = $error;
        }
    }

    protected static function isIgnore(string $msg): bool
    {
        return str_contains($msg, 'stream_select(): Unable to select')
        && str_contains($msg, 'Interrupted system call');
    }

    public function myShutdownHandler(): void
    {
        $error = error_get_last();

        UserCpuTracker::handleShutdown();

        if(empty($error)) {
            return;
        }

        if (self::isIgnore($error['message'])) return;

        $e = new ErrorException(
            $error['message'], 0, $error['type'], $error['file'], $error['line']
        );


        ErrorLog::writeToLog($e);
        //vd($e);
        if(headers_sent()) return;
        http_response_code(500);
    }

    /**
     * @throws ErrorException
     */
    public static function myErrorHandler($level, $message, $file = '', $line = 0)
    {
        throw new ErrorException($message, 0, $level, $file, $line);
    }

    #[NoReturn] private static function apiResponse(Throwable $err, int $httpStatus): void
    {
        $trace = [];
        if (ini_get('display_errors')) {
            $trace = $err->getTrace() ?? [];
            array_unshift($trace, [
                'msg'  => $err->getMessage(),
                'file' => $err->getFile(),
                'line' => $err->getLine()
            ]);
        }
        $type = $err->type ?? 'Err';
        $payload = $err->payload ?? [];
        Response::error(self::getErrorMsg($err), $httpStatus, $trace, $type, $payload);
    }

    public static function myExceptionHandler(Throwable $err): void
    {

        DB::safeRollback();

        $httpStatus = self::getHttpStatus($err);
        if(!isset($err->loggable) || !!$err->loggable){
            ErrorLog::writeToLog($err);
        }

        if (Config::isApi() || Config::isCurl()) {
            self::apiResponse($err, $httpStatus);
        }

        if (!Config::isCLI() && !headers_sent())  {
            http_response_code($httpStatus);
        }

        if (ini_get('display_errors')) {
            echo PHP_EOL;
            echo $err;
            vd($err);
            return;
        }
        if (!empty($msg = self::getErrorMsg($err))) {
            echo $msg;
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
        return $reflectClass->hasMethod('getResponseMsg') ? $err->getResponseMsg() : '';
    }

    protected static function getHttpStatus(Throwable $err): int
    {
        $reflectClass = new ReflectionClass($err::class);
        return $reflectClass->hasMethod('getHttpStatus') ? $err->getHttpStatus() : 500;
    }

    // специально для воркера отдельно
    public static function workerSet(string $folder): void
    {
        set_exception_handler(function(Throwable $e) use ($folder) {
            DB::safeRollback();
            ErrorLog::writeToLog($e);
            Log::msg("UNCAUGHT EXCEPTION: " . $e->getMessage(), [], $folder);
            Log::msg($e->getTraceAsString(), [], $folder);
        });

        set_error_handler(function($severity, $message, $file, $line) use ($folder) {
            if (str_contains($message, 'stream_select(): Unable to select')
                && str_contains($message, 'Interrupted system call')) {
                return true; // Полностью игнорируем эту ошибку
            }
            DB::safeRollback();
            $e = new ErrorException($message, 0, $severity, $file, $line);
            ErrorLog::writeToPHP($e);
            Log::msg("PHP ERROR [$severity]: $message in $file:$line", [], $folder);
            return true;
        });
    }

}