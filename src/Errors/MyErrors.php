<?php

namespace Symphograph\Bicycle\Errors;

use Symphograph\Bicycle\Env\Config;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Helpers;
use Exception;
use Symphograph\Bicycle\Env\Env;

class MyErrors extends Exception
{
    public readonly string $type;
    public bool   $loggable = true;
    public string $logFolder = 'errors';
    public array $payload;

    public function __construct(
        string $message = '',
        private readonly string $pubMsg = '',
        protected int $httpStatus = 500

    )
    {
        parent::__construct($message);
        $this->type = Helpers::classBasename(static::class);
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
        return dirname(ServerEnv::DOCUMENT_ROOT()) . '/logs/errors/' . date('Y-m-d') . '.log';
    }

}