<?php

namespace Symphograph\Bicycle\Logs;


use Error;
use ReflectionClass;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\HTTP\Agent;
use Throwable;

class ErrorLog extends Log
{
    public string    $msg    = '';
    public string    $file   = '';
    public string    $line   = '';
    public array     $trace  = [];
    public string    $code;
    protected string $folder = 'errors';

    public static function writeMsg(string $msg): void
    {
        $err = new Error($msg);
        $log = new self();
        $log->initData($err);
        $log->writeToFile();
        self::writeToPHP($err);
    }

    public static function writeToLog(Throwable $err): void
    {
        $data = new self();
        if(!empty($err->logFolder)){
            $data->folder = $err->logFolder;
        }
        $data->initData($err);
        $data->writeToFile();
        self::writeToPHP($err);
    }

    private function initData(Throwable $err): void
    {
        $this->datetime = date('Y-m-d H:i:s');
        $this->ip = ServerEnv::REMOTE_ADDR();
        $this->script = ServerEnv::SCRIPT_NAME();
        $this->level = 'error';
        $this->type = (new ReflectionClass($err))->getShortName();
        $this->agent = Agent::getSelf();
        $this->method = ServerEnv::REQUEST_METHOD();
        $this->queryString = ServerEnv::QUERY_STRING();
        $this->get = !empty($_GET) ? $_GET : [];
        $this->post = !empty($_POST) ? $_POST : [];
        $this->msg = $err->getMessage();
        $this->file = $err->getFile();
        $this->line = $err->getLine();
        $this->trace = $err->getTrace();
        $this->code = $err->getCode();
    }

    public static function writeToPHP(Throwable $err): void
    {
        ini_set("error_log", Log::createLogPath('/phpErrors/'));
        error_log($err);
    }
}