<?php

namespace Symphograph\Bicycle\Logs;

use ReflectionClass;
use Throwable;

class ErrorLog extends Log
{
    public string    $msg    = '';
    public string    $file   = '';
    public string    $line   = '';
    public array     $trace  = [];
    public string    $code;
    protected string $folder = 'errors';


    public static function writeToLog(Throwable $err): void
    {
        $data = new self();
        $data->initData($err);
        $data->writeToFile();
    }

    private function initData(Throwable $err)
    {
        $this->datetime = date('Y-m-d H:i:s');
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->script = $_SERVER['SCRIPT_NAME'];
        $this->level = 'error';
        $this->type = (new ReflectionClass($err))->getShortName();
        $this->agent = get_browser();
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->queryString = $_SERVER['QUERY_STRING'];
        $this->get = !empty($_GET) ? $_GET : [];
        $this->post = !empty($_POST) ? $_POST : [];
        $this->msg = $err->getMessage();
        $this->file = $err->getFile();
        $this->line = $err->getLine();
        $this->trace = $err->getTrace();
        $this->code = $err->getCode();
    }

    public static function writeToPHP(Throwable $err)
    {
        ini_set("error_log", Log::createLogPath('/logs/phpErrors/'));
        error_log($err);
    }
}