<?php

namespace Symphograph\Bicycle\Errors;

use Symphograph\Bicycle\Api\Response;
use Symphograph\Bicycle\Env\Config;
use Symphograph\Bicycle\Logs\ErrorLog;
use Exception;
use Symphograph\Bicycle\Env\Env;

class MyErrors extends Exception
{
    protected string $type     = 'Err';
    protected bool   $loggable = true;
    public string $logFolder = 'errors';

    public function __construct(
        string $message = '',
        private readonly string $pubMsg = '',
        protected int $httpStatus = 500
    )
    {
        parent::__construct($message);
        /*
        if ($this->loggable) {
            ErrorLog::writeToLog($this);
        }
        */
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPubMsg(): string
    {
        return $this->pubMsg;
    }

    public function getResponseMsg(): string
    {
        return (Env::isDebugMode() || Config::isCurl())
            ? $this->getMessage()
            : $this->getPubMsg();
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus ?? 500;
    }

    public static function getLogFilename(): string
    {
        return dirname($_SERVER['DOCUMENT_ROOT']) . '/logs/errors/' . date('Y-m-d') . '.log';
    }

}