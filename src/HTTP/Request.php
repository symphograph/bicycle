<?php

namespace Symphograph\Bicycle\HTTP;

use JetBrains\PhpStorm\ExpectedValues;
use Symphograph\Bicycle\Errors\ValidationErr;

class Request
{
    public static function get($key, $default=NULL) {
        return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
    }

    public static function post($key, $default=NULL) {
        return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
    }

    public static function cookie($key, $default=NULL) {
        return array_key_exists($key, $_COOKIE) ? $_COOKIE[$key] : $default;
    }

    public static function session($key, $default=NULL) {
        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }

    public static function checkSet(array $required,#[ExpectedValues(['post', 'get'])] string $method='post'): void
    {
        $methods = ['post' => $_POST,'get' => $_GET];
        $method = $methods[$method];
        foreach ($required as $key) {
            if (!isset($method[$key])) {
                throw new ValidationErr("$key missed", 'Нет нужных данных');
            }
        }
    }

    public static function checkEmpty(array $required,#[ExpectedValues(['post', 'get', 'files'])] string $method='post'): void
    {
        $methods = ['post' => $_POST,'get' => $_GET, 'files' => $_FILES];
        $method = $methods[$method];
        foreach ($required as $key) {
            if (empty($method[$key])) {
                throw new ValidationErr("$key is empty", 'Нет нужных данных');
            }
        }
    }

    private static function checkPo()
    {

    }
}