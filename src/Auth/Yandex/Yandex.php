<?php

namespace Symphograph\Bicycle\Auth\Yandex;

use Symphograph\Bicycle\Env\Env;

class Yandex
{
    public static function widgetPage(): string
    {

        $secrets = Env::getYandexSecrets();
        $tokenPage = "https://{$_SERVER['SERVER_NAME']}/auth/ya/token.php";
        $origin = "https://{$_SERVER['SERVER_NAME']}";
        return <<<HTML
            <!DOCTYPE html>
            <html lang="ru">
                <head>
                    <meta charset="utf-8">
                    <title>$secrets->loginPageTitle</title>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                    #AuthArea {
                        width: 100%;
                        display: flex;
                        align-content: center;
                        justify-content: center;
                    }
                    #Auth {
                        width: 100% !important;
                        max-width: 800px;
                    }
                    #Auth>iframe {
                        width: 100% !important;
                    }
                    body {
                        background-color: #2b2d30;
                        display: flex;
                        justify-content: center;
                    }
                    </style>
                  <script src="https://yastatic.net/s3/passport-sdk/autofill/v1/sdk-suggest-with-polyfills-latest.js"></script>
                </head>
                <body>
                <div id="Auth"></div>
               
                    <script>
                    window.onload = () => {
                        YaAuthSuggest.init(
                              {
                                 client_id: '$secrets->clientId',
                                 response_type: 'token',
                                 redirect_uri: '$tokenPage'
                              },
                              '$origin',
                              {
                                  parentId: 'Auth'
                              }
                           )
                           .then(({
                              handler
                           }) => handler())
                           .then((data) => {
                               console.log('Сообщение с токеном', data)
                           })
                           .catch(error => console.log('Обработка ошибки', error));
                    }
                        
                    </script>
              
                

                </body>
            </html>
        HTML;
    }
}