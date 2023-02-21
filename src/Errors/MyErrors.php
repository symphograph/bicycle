<?php

namespace Symphograph\Bicycle\Errors;

use App\Logs\ErrorLog;
use Exception;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\FileHelper;

class MyErrors extends Exception
{
    protected string $type     = 'Err';
    protected bool   $loggable = true;

    public function __construct(string $message, private string $pubMsg = '', protected int $httpStatus = 500)
    {
        parent::__construct($message);
        if ($this->loggable) {
            ErrorLog::writeToLog($this);
        }

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
        return Env::isDebugMode() ? $this->getMessage() : $this->getPubMsg();
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