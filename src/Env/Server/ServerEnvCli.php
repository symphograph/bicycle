<?php

namespace Symphograph\Bicycle\Env\Server;

use Symphograph\Bicycle\Env\Env;

class ServerEnvCli extends ServerEnv
{
    public function __construct()
    {
        self::initDOCUMENT_ROOT();
        self::initSERVER_NAME();
        self::initREMOTE_ADDR();
        self::initSCRIPT_NAME();
        self::initREQUEST_METHOD();
    }


    private function initDOCUMENT_ROOT(): void
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        $this->DOCUMENT_ROOT = getRoot() . '/public_html';
    }

    private function initSERVER_NAME(): void
    {
        $this->SERVER_NAME = Env::getServerName();
    }

    private function initREMOTE_ADDR(): void
    {
        $this->REMOTE_ADDR = Env::getDebugIps()[0];
    }

    private function initSCRIPT_NAME(): void
    {
        global $argv;
        if(isset($argv)){
            $this->SCRIPT_NAME = $argv[0];
        }

    }

    private function initREQUEST_METHOD(): void
    {
        $this->REQUEST_METHOD = 'POST';
    }

}