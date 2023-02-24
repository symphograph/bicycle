<?php

namespace Symphograph\Bicycle\Logs;

use JsonException;
use Symphograph\Bicycle\FileHelper;

class Log
{
    protected string $folder = 'any';

    public string $datetime;
    public string $method;
    public string $ip;
    public string $script;
    public string $level = 'info';
    public string $type  = 'any';
    public object $agent;
    public array  $get;
    public array  $post;
    public string $queryString;


    protected function writeToFile(): void
    {
        $data = self::getJson();
        $logPath = self::createLogPath("/logs/$this->folder/");

        $log = fopen($logPath, 'a+');
        fwrite($log, "$data" . PHP_EOL);
        fclose($log);
    }

    private function getJson(): string
    {
        try {
            $data = json_encode($this, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_THROW_ON_ERROR);
        }catch (\Throwable $exception) {
            return '{}' . PHP_EOL;
        }
        return $data;
    }

    public static function createLogPath(string $logFolder): string
    {
        $logPath = dirname($_SERVER['DOCUMENT_ROOT']) . $logFolder . date('Y-m-d') . '.log';
        if (!file_exists($logPath)) {
            FileHelper::fileForceContents($logPath, '');
        }
        return $logPath;
    }
}