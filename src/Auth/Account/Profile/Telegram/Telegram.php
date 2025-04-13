<?php
namespace Symphograph\Bicycle\Auth\Account\Profile\Telegram;

use Symphograph\Bicycle\Env\Env;
use Symphograph\Bicycle\Errors\Auth\AuthErr;

class Telegram
{
    public string   $err      = '';
    public TeleUser $TeleUser;

    public function __construct(){}

    /**
     * @throws AuthErr
     */
    public static function auth() : TeleUser
    {
        $Login = new self();
        $auth_data = $Login->checkTelegramAuthorization();
        $Login->saveTelegramUserData($auth_data);

        return TeleUser::byBind($auth_data);
    }

    /**
     * @throws AuthErr
     */
    private function checkTelegramAuthorization() : array
    {
        if(empty($_GET['hash'])){
            throw new AuthErr('Telegram hash is empty');
        }
        $auth_data = $_GET;
        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);

        $this->checkHash($auth_data,$check_hash);
        $this->checkDate($auth_data);

        return $auth_data;
    }

    /**
     * @throws AuthErr
     */
    private function checkDate($auth_data) :void
    {
        $date = $auth_data['auth_date'] ?? 0;
        if(!$date){
            throw new AuthErr('Telegram Date is empty');
        }

        if((time() - $date) > 86400){
            throw new AuthErr('Telegram Date is expired');
        }
    }

    /**
     * @throws AuthErr
     */
    private function checkHash(array $auth_data, string $check_hash) : void
    {

        if(!count($auth_data)){
            throw new AuthErr('Telegram auth_data is empty');
        }

        $hash = $this->getHash($auth_data);

        if(!hash_equals($hash, $check_hash)) {
            throw new AuthErr('Telegram hash is invalid');
        }
    }

    public static function getHash(array $TeleUserProps): string
    {
        $token = Env::getTelegramSecrets()->getKey();

        $data_check_arr = [];
        foreach ($TeleUserProps as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $secret_key        = hash('sha256', $token, true);

        return hash_hmac('sha256', $data_check_string, $secret_key);
    }

    private function saveTelegramUserData($auth_data) : void
    {
        $auth_data_json = json_encode($auth_data);
        $is = setcookie('tg_user', $auth_data_json);
        if(!$is) throw new AuthErr('Telegram cant set cookie');
    }

    public static function widgetPage(string $title, string $botName, string $callbackUrl): string
    {
        $script = self::widgetScript($botName, $callbackUrl);
        return <<<HTML
            <!DOCTYPE html>
            <html lang="ru">
              <head>
                <meta charset="utf-8">
                <title>$title</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
              </head>
              <body><div style="text-align: center;">$script</div></body>
            </html>
        HTML;
    }

    private static function widgetScript(string $botName, string $callbackUrl): string
    {
        $url = 'https://telegram.org/js/telegram-widget.js?15';

        return <<<HTML
            <div style="padding: 3em">
                <script type="text/javascript" async 
                    src="$url" 
                    data-telegram-login="$botName" 
                    data-size="large" 
                    data-auth-url="$callbackUrl" 
                    data-request-access="write">
                </script>
            </div>
            HTML;
    }
}