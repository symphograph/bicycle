<?php

namespace Symphograph\Bicycle\Api;

use Curl\Curl;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\CurlErr;
use Symphograph\Bicycle\Logs\ErrorLog;

class CurlAPI
{
    public function __construct(
        protected string $apiName,
        protected string $url,
        protected array $params,
    )
    {

    }

    public function post()
    {
        try{
            $curl = new Curl();
            //$curl->setBasicAuthentication('username', 'password');
            //$curl->setUserAgent('MyUserAgent/0.0.1 (+https://www.example.com/bot.html)');
            $curl->setReferrer('https://' . $_SERVER['SERVER_NAME'] . '/test/001.php');
            $curl->setHeader('X-Requested-With', 'XMLHttpRequest');
            $curl->setHeader('Accept', 'application/json');
            $curl->setHeader('Origin', 'https://' . $_SERVER['SERVER_NAME']);
            $curl->setHeader('Authorization', Env::getApiKey());
            //$curl->setCookie('key', 'value');
            $domain = ENV::getAPIDomains()['ussoStaff'];
            $curl->post("https://$domain$this->url", $this->params);
            if ($curl->error) {
                throw new CurlErr('Error: ' . $curl->errorMessage);
            }
        } catch (\Throwable $err) {

            ErrorLog::writeToPHP($err);

            return false;
        }



        return $curl->response;
    }
}