<?php
namespace Symphograph\Bicycle\Auth\Telegram;
use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Env\Server\ServerEnv;
use Symphograph\Bicycle\Errors\AuthErr;

class Telegram
{

    private string  $token    = '';
    private string  $bot_name = '';
    public string   $err      = '';
    public TeleUser $TeleUser;

    public function __construct()
    {
        $Secrets = Env::getTelegramSecrets();
        $this->token = $Secrets->token ?? '';
        $this->bot_name = $Secrets->bot_name ?? '';
    }

    public static function auth() : TeleUser|bool
    {
        $Login = new self();
        $auth_data = $Login->checkTelegramAuthorization();
        if(!$auth_data){
            return false;
        }

        if(!$Login->saveTelegramUserData($auth_data)){
            return false;
        }

        return TeleUser::byBind($auth_data);
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getBotName()
    {
        return $this->bot_name;
    }

    public function checkTelegramAuthorization() : array|bool
    {
        if(empty($_GET['hash'])){
            return false;
        }
        $auth_data = $_GET;
        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);

        if(!self::checkHash($auth_data,$check_hash)){
            throw new AuthErr('Telegram hash is invalid');
        }

        if(!self::checkDate($auth_data)){
            return false;
        }

        return $auth_data;
    }

    private function checkDate($auth_data) : bool
    {
        $date = $auth_data['auth_date'] ?? 0;
        if(!$date){
            $this->err = 'Date is empty';
            return false;
        }

        if((time() - $date) > 86400){
            $this->err = 'Date is outdated';
            return false;
        }

        return true;
    }

    private function checkHash(array $auth_data, string $check_hash) : bool
    {

        if(!count($auth_data)){
            return false;
        }

        $hash = $this->getHash($auth_data);

        if(strcmp($hash, $check_hash) !== 0) {
            $this->err = 'Data is NOT from Telegram';
            return false;
        }
        return true;
    }

    public function getHash(array $TeleUserProps): string
    {
        $data_check_arr = [];
        foreach ($TeleUserProps as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $secret_key        = hash('sha256', $this->token, true);
        $hash              = hash_hmac('sha256', $data_check_string, $secret_key);
        return $hash;
    }

    public function saveTelegramUserData($auth_data) : bool
    {
        $auth_data_json = json_encode($auth_data);
        return setcookie('tg_user', $auth_data_json);
    }

    public static function widgetPage(string $title, string $callbackUrl): string
    {
        $script = self::widgetScript($callbackUrl);
        return <<<HTML
            <!DOCTYPE html>
            <html lang="ru">
              <head>
                <meta charset="utf-8">
                <title>$title</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
              </head>
              <body><center>$script</center></body>
            </html>
        HTML;
    }

    public static function widgetScript(string $callbackUrl): string
    {
        $serverName = ServerEnv::SERVER_NAME();
        $botName = Env::getTelegramSecrets()->bot_name;
        return <<<HTML
            <div style="padding: 3em">
                <script type="text/javascript" async 
                    src="https://telegram.org/js/telegram-widget.js?15" 
                    data-telegram-login="$botName" 
                    data-size="large" 
                    data-auth-url="https://$serverName/$callbackUrl" 
                    data-request-access="write">
                </script>
            </div>
            HTML;
    }
}