<?php

namespace Symphograph\Bicycle\Env;

use Symphograph\Bicycle\Logs\{ErrorLog, Log};
use ErrorException;
use ReflectionClass;
use Symphograph\Bicycle\Api\Response;
use Symphograph\Bicycle\Errors\ConfigErr;
use Throwable;

class Config
{
    public static function redirectFromWWW(): void
    {
        if (!preg_match('/www./', $_SERVER['SERVER_NAME'])){
            return;
        }
        $server_name = str_replace('www.', '', $_SERVER['SERVER_NAME']);
        $ref = $_SERVER["QUERY_STRING"];
        if ($ref != "") $ref = "?" . $ref;

        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $server_name . "/" . $ref);
        exit();
    }

    public static function initDisplayErrors(): void
    {
        if (Env::isDebugMode()) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        }
    }

    public static function checkPermission(): void
    {
        if(Env::isDebugMode()){
            return;
        }
        $folders = Env::getDebugOnlyFolders();
        foreach ($folders as $folder){
            if(str_starts_with($_SERVER['SCRIPT_NAME'], '/' . $folder . '/')){
                throw new ConfigErr('debugOnlyFolders permits', 'Недостаточно прав', 403);
            }
        }
    }

    public static function initApiSettings(): void
    {
        if (!self::isApi()) {
            return;
        }

        if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'OPTIONS'])){
            throw new ConfigErr('invalid method', 'invalid method', 405);
        }

        self::checkOrigin();

        if (empty($_POST)) {
            $_POST = json_decode(file_get_contents('php://input'), true)['params'] ?? [];
        }
        if (empty($_POST['token'])  && empty($_SERVER['HTTP_AUTHORIZATION'])) {
            throw new ConfigErr('emptyToken', 'emptyToken', 405);
        }

    }

    private static function isApi(): bool
    {
        return str_starts_with($_SERVER['SCRIPT_NAME'], '/api/');
    }

    private static function checkOrigin(): void
    {
        if (empty($_SERVER['HTTP_ORIGIN'])){
            throw new ConfigErr('emptyOrigin', 'emptyOrigin', 401);
        }

        $adr = 'https://' . Env::getFrontendDomain();
        if($_SERVER['HTTP_ORIGIN'] !== $adr){
            throw new ConfigErr('Unknown domain', 'Unknown domain', 401);
        }
    }

    public static function regHandlers(): void
    {
        $selfClass = new self();
        set_error_handler([$selfClass,'myErrorHandler']);
        set_exception_handler([$selfClass,'myExceptionHandler']);
        register_shutdown_function([$selfClass,'myShutdownHandler']);
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

        ErrorLog::writeToLog($err);
        if(self::isApi()){
            Response::error(self::getErrorMsg($err));
        }

        $httpStatus = self::getHttpStatus($err);
        http_response_code($httpStatus);
        if (ini_get('display_errors')) {
            echo $err;
            return;
        }
        echo "<h1>Произошла чудовищная ошибка сервера</h1>
          Мы уже работаем над её исправлением.<br>";
    }

    private static function getErrorMsg(Throwable $err): string
    {
        if(ini_get('display_errors')){
            return $err->getMessage();
        }
        $reflectClass = new ReflectionClass($err::class);
        if($reflectClass->hasMethod('getResponseMsg')){
            return $err->getResponseMsg();
        }
        return '';
    }

    private static function getHttpStatus(Throwable $err): int
    {
        $reflectClass = new ReflectionClass($err::class);
        if($reflectClass->hasMethod('getHttpStatus')){
            return $err->getHttpStatus();
        }
        return 500;
    }
}