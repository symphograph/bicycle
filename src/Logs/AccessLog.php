<?php

namespace Symphograph\Bicycle\Logs;

use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\HTTP\Agent;

class AccessLog extends Log
{

    public static function writeToLog(): void
    {
        $data = new self();
        $data->initData();
        $data->writeToFile();
    }


    private function initData(): void
    {
        $this->datetime = date('Y-m-d H:i:s');
        $this->ip = ServerEnv::REMOTE_ADDR();
        $this->script = ServerEnv::SCRIPT_NAME();
        $this->level = 'info';
        $this->type = 'access';
        $this->agent = Agent::getSelf();
        $this->method = ServerEnv::REQUEST_METHOD();
        $this->queryString = ServerEnv::QUERY_STRING();
        $this->get = !empty($_GET) ? $_GET : [];
        $this->post = !empty($_POST) ? $_POST : [];
    }
}