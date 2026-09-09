<?php

namespace Symphograph\Bicycle\Api;

use Curl\Curl;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\CurlErr;
use Symphograph\Bicycle\Errors\NoContentErr;
use Symphograph\Bicycle\Token\CurlToken;


class CurlAPI
{
    public function __construct(protected string $assessToken = '')
    {
        //$this->assessToken = CurlToken::create([1]);
    }

    public function setToken(string $token): static
    {
        $this->assessToken = $token;
        return $this;
    }

    public function post(string $url, array $params): Response|ResponseErr
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
        if(!empty($this->assessToken)) {
            $curl->setHeader('Accesstoken', $this->assessToken);
        }

        $curl->post($url, $params);

        /*
        $res = new Response();
        $res->httpStatus = $curl->httpStatusCode;
        $res->content = $curl->response;

        if($curl->httpStatusCode === 406){
            throw new NoContentErr();
        }

        if ($curl->error) {
            $msg = $curl->errorMessage . ' ' . ($curl->response->error ?? '| empty msg');
            throw new CurlErr(message: $msg , httpStatus: $curl->httpStatusCode);
        }

        if (!is_object($curl->response)) {
            throw new CurlErr('Empty Response ');
        }

        if (empty($curl->response->result)) {
            throw new CurlErr('Invalid Result ');
        }
         */
        //echo $curl->response;
        $response = $curl->error
            ? ResponseErr::byBind($curl->response)
            : Response::byBind($curl->response);

        $response->httpStatus = $curl->httpStatusCode;
        if($curl->error) {
            $response->error = $curl->errorMessage;
            //vd($response);
        }



        return $response;
    }

}