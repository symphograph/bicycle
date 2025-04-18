<?php

namespace Symphograph\Bicycle\Env;

use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Env\Services\Client;
use Symphograph\Bicycle\Errors\Auth\EmptyOriginErr;
use Symphograph\Bicycle\Errors\Auth\InvalidOriginErr;
use Symphograph\Bicycle\Errors\ConfigErr;
use Symphograph\Bicycle\Errors\ValidationErr;


class Config
{
    /**
     * Выполняет перенаправление с версии сайта с "www" на версию без "www".
     *
     * @return void
     */
    public static function redirectFromWWW(): void
    {
        $serverName = ServerEnv::SERVER_NAME();
        if (!str_starts_with($serverName, 'www.')) {
            return;
        }

        $serverName = substr($serverName, 4);

        $queryString = ServerEnv::QUERY_STRING();
        $queryString = !empty($queryString) ? '?' . $queryString : '';
        $redirectURL = "https://" . $serverName . ServerEnv::REQUEST_URI() . $queryString;

        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $redirectURL);

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
        if (Env::isDebugMode() || Env::isDebugIp()) {
            return;
        }
        $folders = Env::getDebugOnlyFolders();
        foreach ($folders as $folder) {
            if (str_starts_with(ServerEnv::SCRIPT_NAME(), '/' . $folder . '/')) {
                throw new ConfigErr('debugOnlyFolders permits', 'Недостаточно прав', 403);
            }
        }
    }

    protected static function initEndPoint(string $path, array $allowedMethods, array $expectedHeaders = []): void
    {
        if(!str_starts_with(ServerEnv::SCRIPT_NAME(), $path)){
            return;
        }

        if(self::isApi()){
            if (empty($_POST['method'])) {
                throw new ValidationErr('method is empty');
            }
        }

        if (!in_array(ServerEnv::REQUEST_METHOD(), $allowedMethods)) {
            throw new ConfigErr('invalid method', 'invalid method', 405);
        }

        self::checkExpectedHeaders($expectedHeaders);
    }

    protected static function checkExpectedHeaders(array $expectedHeaders): void
    {
        foreach ($expectedHeaders as $expectedHeader => $expectedValue){
            if(empty($_SERVER[$expectedHeader])){
                throw new ConfigErr($expectedHeader . ' is empty', '', 401);
            }

            if(empty($expectedValue)) continue;

            if($_SERVER[$expectedHeader] !== $expectedValue){
                throw new ConfigErr('invalid ' . $expectedHeader, '', 403);
            }
        }
    }

    public static function isApi(): bool
    {
        $sn = ServerEnv::SCRIPT_NAME();
        return str_starts_with($sn, '/epoint/');
    }

    public static function isCurl(): bool
    {
        return str_starts_with(ServerEnv::SCRIPT_NAME(), '/curl/');
    }

    public static function isCLI(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * @throws EmptyOriginErr
     * @throws InvalidOriginErr
     */
    protected static function checkOrigin(): void
    {
        if(!self::isApi() && !self::isCurl()){
            return;
        }
        $origin = ServerEnv::HTTP_ORIGIN();
        if(empty($origin)) throw new EmptyOriginErr();

        Client::byOrigin($origin);
    }

    public static function postHandler(): void
    {
        if (empty($_POST)) {
            $_POST = json_decode(file_get_contents('php://input'), true)['params'] ?? [];
        }
    }

    public static function cookOpts(
        int         $expires = 0,
        string      $path = '/',
        string|null $domain = null,
        bool        $secure = true,
        bool        $httponly = true,
        string      $samesite = 'Strict', // None || Lax  || Strict
        bool        $debug = false
    ) : array
    {
        if(!$expires){
            $expires = time() + 60*60*24*30;
        }

        if($debug){
            return [
                'expires'  => $expires,
                'path'     => '/',
                'domain'   => null,
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'None'
            ];
        }
        return [
            'expires'  => $expires,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite // None || Lax  || Strict
        ];
    }

}