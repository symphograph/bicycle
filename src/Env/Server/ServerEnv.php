<?php

namespace Symphograph\Bicycle\Env\Server;

/**
 * Абстрактный класс, представляющий окружение сервера.
 *
 * @package Symphograph\Bicycle\Env\Server
 */
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
    protected string $HTTP_REFERER;


    private static function getServerEnv(): ServerEnvITF
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        return getServerEnvClass();
    }

    /**
     * Получает имя сервера.
     *
     * @return string Имя сервера.
     */
    public static function SERVER_NAME(): string
    {
        return self::getServerEnv()->SERVER_NAME;
    }

    /**
     * Получает IP-адрес клиента.
     *
     * @return string IP-адрес клиента.
     */
    public static function REMOTE_ADDR(): string
    {
        return self::getServerEnv()->REMOTE_ADDR;
    }

    /**
     * Получает имя скрипта.
     *
     * @return string Имя скрипта.
     */
    public static function SCRIPT_NAME(): string
    {
        return self::getServerEnv()->SCRIPT_NAME;
    }

    /**
     * Получает метод запроса (GET, POST и т. д.).
     *
     * @return string Метод запроса.
     */
    public static function REQUEST_METHOD(): string
    {
        return self::getServerEnv()->REQUEST_METHOD;
    }

    /**
     * Получает строку запроса.
     *
     * @return string Строка запроса.
     */
    public static function QUERY_STRING(): string
    {
        return self::getServerEnv()->QUERY_STRING;
    }

    /**
     * Получает корневой каталог документов сервера.
     *
     * @return string Корневой каталог документов сервера.
     */
    public static function DOCUMENT_ROOT(): string
    {
        return self::getServerEnv()->DOCUMENT_ROOT;
    }

    /**
     * Получает токен доступа HTTP-заголовка.
     *
     * @return string Токен доступа.
     */
    public static function HTTP_ACCESSTOKEN(): string
    {
        return self::getServerEnv()->HTTP_ACCESSTOKEN;
    }

    /**
     * Получает URI запроса.
     *
     * @return string URI запроса.
     */
    public static function REQUEST_URI(): string
    {
        return self::getServerEnv()->REQUEST_URI;
    }

    /**
     * Получает происхождение HTTP-заголовка.
     *
     * @return string Происхождение HTTP-заголовка.
     */
    public static function HTTP_ORIGIN(): string
    {
        return self::getServerEnv()->HTTP_ORIGIN;
    }

    /**
     * Получает HTTP-заголовок Referer.
     *
     * @return string HTTP-заголовок Referer.
     */
    public static function HTTP_REFERER(): string
    {
        return self::getServerEnv()->HTTP_REFERER;
    }
}
