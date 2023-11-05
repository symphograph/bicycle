<?php

namespace Symphograph\Bicycle\Api;

use Curl\Curl;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\ApiErr;
use Symphograph\Bicycle\Errors\CurlErr;
use Symphograph\Bicycle\Errors\NoContentErr;
use Symphograph\Bicycle\Errors\ValidationErr;
use Symphograph\Bicycle\Logs\ErrorLog;
use Symphograph\Bicycle\Token\CurlToken;
use Throwable;

class CurlAPI
{
    private string $url = '';
    protected string $assessToken = '';

    public function __construct(
        protected string $apiName,
        protected string $path,
        protected array  $params,
    )
    {
        $domain = ENV::getAPIDomains()[$this->apiName];
        $this->url = "https://$domain$path";
        $this->assessToken = CurlToken::create([1]);
    }

    public function post(): Response
    {
        $curl = new Curl();

        /*
        // $curl->setBasicAuthentication('username', 'password');
        // $curl->setUserAgent('MyUserAgent/0.0.1 (+https://www.example.com/bot.html)');
        // $curl->setCookie('key', 'value');
        */

        $curl->setReferrer('https://' . ServerEnv::SERVER_NAME() . '/test/001.php');
        $curl->setHeader('X-Requested-With', 'XMLHttpRequest');
        $curl->setHeader('Accept', 'application/json');
        $curl->setHeader('Origin', 'https://' . ServerEnv::SERVER_NAME());
        $curl->setHeader('Authorization', Env::getApiKey());
        $curl->setHeader('Accesstoken', $this->assessToken);


        $curl->post($this->url, $this->params);

        if($curl->httpStatusCode === 406){
            throw new NoContentErr();
        }

        if ($curl->error) {
            $msg = $curl->errorMessage . ' ' . ($curl->response->error ?? '');
            throw new CurlErr(message: $msg , httpStatus: $curl->httpStatusCode);
        }

        if (!is_object($curl->response)) {
            throw new CurlErr('Empty Response ');
        }

        if (empty($curl->response->result)) {
            throw new CurlErr('Invalid Result ');
        }

        $response = Response::byBind($curl->response);
        $response->httpStatus = $curl->httpStatusCode;
        return $response;
    }
}