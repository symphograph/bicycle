<?php

namespace Symphograph\Bicycle\Logs;

use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\FileHelper;
use Symphograph\Bicycle\HTTP\Agent;
use Throwable;

class Log
{
    protected string $folder = 'any';

    public string $datetime;
    public string $method;
    public string $ip;
    public string $script;
    public string $level = 'info';
    public string $type  = 'any';
    public Agent  $agent;
    public array  $get;
    public array  $post;
    public string $queryString;

    public static function msg(string $msg, array $data = [], string $logFolder = 'tmpLog'): void
    {
        $datetime = date('Y-m-d H:i:s');
        foreach ($data as $k => $v){
            $msg .= PHP_EOL . $k . ' - ' . $v;
        }
        $msg = "[$datetime UTC] - $msg";
        self::writeData($msg, $logFolder);
    }

    protected function writeToFile(): void
    {
        $data = self::getJson();
        self::writeData($data, $this->folder);
    }

    public static function writeData(string $data, string $logFolder): void
    {
        $logPath = self::createLogPath($logFolder);
        $log = fopen($logPath, 'a+');
        fwrite($log, "$data" . PHP_EOL);
        fclose($log);
    }

    private function getJson(): string
    {
        try {
            $data = json_encode($this, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_THROW_ON_ERROR);
        }catch (Throwable) {
            return '{}' . PHP_EOL;
        }
        return $data;
    }

    public static function createLogPath(string $logFolder): string
    {
        $logPath = dirname(ServerEnv::DOCUMENT_ROOT()) . '/logs/' . $logFolder . '/' . date('Y-m-d') . '.log';
        if (!file_exists($logPath)) {
            FileHelper::fileForceContents($logPath, '');
        }
        return $logPath;
    }
}