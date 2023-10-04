<?php

namespace Symphograph\Bicycle\Env\Server;

abstract class ServerEnv implements ServerEnvITF
{
    protected string $SERVER_NAME;
    protected string $REMOTE_ADDR;
    protected string $SCRIPT_NAME;
    protected string $REQUEST_METHOD;
    protected string $HTTP_USER_AGENT;
    protected string $HTTP_HOST;
    protected string $SERVER_PORT;
    protected string $QUERY_STRING = '';
    protected string $DOCUMENT_ROOT;
    protected string $HTTP_ACCESSTOKEN = '';
    protected string $REQUEST_URI = '';
    protected string $HTTP_ORIGIN = '';

    private static function getServerEnv(): ServerEnvITF
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        return getServerEnv();
    }

    public static function SERVER_NAME(): string
    {
        return self::getServerEnv()->SERVER_NAME;
    }

    public static function REMOTE_ADDR(): string
    {
        return self::getServerEnv()->REMOTE_ADDR;
    }

    public static function SCRIPT_NAME(): string
    {
        return self::getServerEnv()->SCRIPT_NAME;
    }

    public static function REQUEST_METHOD(): string
    {
        return self::getServerEnv()->REQUEST_METHOD;
    }

    public static function QUERY_STRING(): string
    {
        return self::getServerEnv()->QUERY_STRING;
    }

    public static function DOCUMENT_ROOT(): string
    {
        return self::getServerEnv()->DOCUMENT_ROOT;
    }

    public static function HTTP_ACCESSTOKEN(): string
    {
        return self::getServerEnv()->HTTP_ACCESSTOKEN;
    }

    public static function REQUEST_URI(): string
    {
        return self::getServerEnv()->REQUEST_URI;
    }

    public static function HTTP_ORIGIN(): string
    {
        return self::getServerEnv()->REQUEST_URI;
    }

}
