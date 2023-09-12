<?php

namespace Symphograph\Bicycle\HTTP;

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
}