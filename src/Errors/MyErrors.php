<?php

namespace Symphograph\Bicycle\Errors;

use Exception;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\FileHelper;

class MyErrors extends Exception
{
    protected string $type = 'Err';
    protected bool $loggable = true;

    public function __construct(string $message, private string $pubMsg = '', protected int $httpStatus = 500)
    {
        parent::__construct($message);
        if($this->loggable){
            self::writLog();
        }

    }

    private function writLog(): void
    {
        //$logText = self::prepLog();
        $data = [
            'datetime' => date('Y-m-d H:i:s'),
            'type' => $this->type,
            'level' => 'error',
            'msg' => $this->getMessage(),
            'trace' => self::prepTrace(),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'agent' => get_browser()
        ];
        //$data = json_encode($data);
        $data = serialize($data);
        $file = self::getLogFilename();
        if(!file_exists($file)){
            FileHelper::fileForceContents($file, '');
        }
        $log = fopen($file, 'a+');
        fwrite($log, "$data\r\n");
        fclose($log);
    }

    private function prepTrace(): string
    {
        if (!count(self::getTrace())) {
            return $_SERVER['SCRIPT_NAME'] . "({$this->getLine()})";
        }
        $trace = self::getTrace();
        return json_encode($trace);
    }

    public function getPubMsg(): string
    {
        return $this->pubMsg;
    }

    public function getResponseMsg(): string
    {
        return Env::isDebugMode() ? $this->getMessage() : $this->getPubMsg();
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus ?? 500;
    }

    public static function getLogFilename(): string
    {
        return dirname($_SERVER['DOCUMENT_ROOT']). '/logs/errors/' . date('Y-m-d') . '.log';
    }
}