<?php

namespace Symphograph\Bicycle\Api;

use Curl\Curl;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\ApiErr;
use Symphograph\Bicycle\Errors\CurlErr;
use Symphograph\Bicycle\Errors\NoContentErr;
use Symphograph\Bicycle\Errors\ValidationErr;
use Symphograph\Bicycle\Logs\ErrorLog;
use Throwable;

class CurlAPI
{
    public function __construct(
        protected string $apiName,
        protected string $url,
        protected array  $params,
        protected string $assessToken = ''
    )
    {

    }

    public function post(): object
    {
        $curl = new Curl();
        //$curl->setBasicAuthentication('username', 'password');
        //$curl->setUserAgent('MyUserAgent/0.0.1 (+https://www.example.com/bot.html)');
        $curl->setReferrer('https://' . $_SERVER['SERVER_NAME'] . '/test/001.php');
        $curl->setHeader('X-Requested-With', 'XMLHttpRequest');
        $curl->setHeader('Accept', 'application/json');
        $curl->setHeader('Origin', 'https://' . $_SERVER['SERVER_NAME']);
        $curl->setHeader('Authorization', Env::getApiKey());
        $curl->setHeader('Accesstoken', $this->assessToken);
        //$curl->setCookie('key', 'value');
        $domain = ENV::getAPIDomains()[$this->apiName];
        $curl->post("https://$domain$this->url", $this->params);
        if ($curl->error) {
            if($curl->httpStatusCode === 406){
                throw new NoContentErr();
            }
            $msg = $curl->errorMessage . ($curl->response->error ?? '');
            $pubMsg = $curl->response->error ?? 'Ой! Ошибочка...';
            throw new CurlErr($msg , $msg, httpStatus: $curl->httpStatusCode);
        }



        if (!is_object($curl->response)) {
            throw new CurlErr('Empty Response ');
        }

        if (empty($curl->response->result)) {
            throw new CurlErr('Invalid Result ');
        }

        return $curl->response;
    }
}