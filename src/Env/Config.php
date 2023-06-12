<?php

namespace Symphograph\Bicycle\Env;

use Symphograph\Bicycle\Errors\ConfigErr;


class Config
{
    public static function redirectFromWWW(): void
    {
        if (!preg_match('/www./', $_SERVER['SERVER_NAME'])) {
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
        if (Env::isDebugMode()) {
            return;
        }
        $folders = Env::getDebugOnlyFolders();
        foreach ($folders as $folder) {
            if (str_starts_with($_SERVER['SCRIPT_NAME'], '/' . $folder . '/')) {
                throw new ConfigErr('debugOnlyFolders permits', 'Недостаточно прав', 403);
            }
        }
    }

    protected static function initEndPoint(string $path, array $allowedMethods, array $requiredHeaders = []): void
    {
        if(!str_starts_with($_SERVER['SCRIPT_NAME'], $path)){
            return;
        }

        if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
            throw new ConfigErr('invalid method', 'invalid method', 405);
        }

        self::checkRequiredHeaders($requiredHeaders);
    }

    protected static function checkRequiredHeaders(array $requiredHeaders): void
    {
        foreach ($requiredHeaders as $requiredHeader){
            if(empty($_SERVER[$requiredHeader])){
                $msg = $requiredHeader . ' is empty';
                throw new ConfigErr($msg, $msg, 401);
            }
        }
    }

    public static function isApi(): bool
    {
        return str_starts_with($_SERVER['SCRIPT_NAME'], '/api/');
    }

    protected static function checkOrigin(): void
    {
        if(!self::isApi()){
            return;
        }
        if (empty($_SERVER['HTTP_ORIGIN'])) {
            throw new ConfigErr('emptyOrigin', 'emptyOrigin', 401);
        }
        in_array($_SERVER['HTTP_ORIGIN'], Env::getClientDomains('https://'))
        or throw new ConfigErr('Unknown domain', 'Unknown domain', 401);
    }

    public static function postHandler(): void
    {
        if (empty($_POST)) {
            $_POST = json_decode(file_get_contents('php://input'), true)['params'] ?? [];
        }
    }

}