<?php

namespace Symphograph\Bicycle\Logs;

class AccessLog extends Log
{

    public static function writeToLog(): void
    {
        $data = new self();
        $data->initData();
        $data->writeToFile();
    }


    private function initData()
    {
        $this->datetime = date('Y-m-d H:i:s');
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->script = $_SERVER['SCRIPT_NAME'];
        $this->level = 'info';
        $this->type = 'access';
        $this->agent = get_browser();
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->queryString = $_SERVER['QUERY_STRING'];
        $this->get = !empty($_GET) ? $_GET : [];
        $this->post = !empty($_POST) ? $_POST : [];
    }
}