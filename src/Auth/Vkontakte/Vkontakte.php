<?php

namespace Symphograph\Bicycle\Auth\Vkontakte;

use Symphograph\Bicycle\Env\Env;

class Vkontakte
{
    public static function widgetPage(): string
    {

        $secrets = Env::getVKSecrets();
        $url = 'https://' . $_SERVER['SERVER_NAME'] . $secrets->callback;
        return <<<HTML
            <!DOCTYPE html>
            <html lang="ru">
                <head>
                    <meta charset="utf-8">
                    <title>$secrets->loginPageTitle</title>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                    #vkAuthArea {
                        width: 100%;
                        display: flex;
                        align-content: center;
                        justify-content: center;
                    }
                    #vkAuth {
                        width: 100% !important;
                        max-width: 800px;
                    }
                    #vkAuth>iframe {
                        width: 100% !important;
                    }
                    body {
                        background-color: #2b2d30;
                    }
                    </style>
                    <script src="https://vk.com/js/api/openapi.js?169" type="text/javascript"></script>
                </head>
                <body>
                    <script type="text/javascript">
                        VK.init({
                        apiId: $secrets->appId
                        })
                    </script>
                    <div id="vkAuthArea"><div id="vkAuth"></div></div>              
                    <script type="text/javascript">
                         VK.Widgets.Auth('vkAuth', {
                             authUrl: "$url",
                             width: '100%'
                         });
                    </script>
                </body>
            </html>
        HTML;
    }


}