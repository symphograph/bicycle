<?php
namespace Symphograph\Bicycle\Auth\Telegram;
class Telegram
{

    private string $token = '';
    private string $bot_name = '';
    public string $err = '';
    public TeleUser $TeleUser;

    public function __construct()
    {
        $cfg = require dirname($_SERVER['DOCUMENT_ROOT']) . '/includs/ip.php';
        $this->token = $cfg->telegram[$_SERVER['SERVER_NAME']]['token'] ?? '';
        $this->bot_name = $cfg->telegram[$_SERVER['SERVER_NAME']]['bot_name'] ?? '';
    }

    public static function auth() : TeleUser|bool
    {
        $Login = new Telegram();
        $auth_data = $Login->checkTelegramAuthorization();
        if(!$auth_data){
            return false;
        }

        if(!$Login->saveTelegramUserData($auth_data)){
            return false;
        }

        return TeleUser::byData($auth_data);
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
            return false;
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

        $data_check_arr = [];
        foreach ($auth_data as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $secret_key        = hash('sha256', $this->token, true);
        $hash              = hash_hmac('sha256', $data_check_string, $secret_key);
        if(strcmp($hash, $check_hash) !== 0) {
            $this->err = 'Data is NOT from Telegram';
            return false;
        }
        return true;
    }

    public function saveTelegramUserData($auth_data) : bool
    {
        $auth_data_json = json_encode($auth_data);
        return setcookie('tg_user', $auth_data_json);
    }

    public function anonymous()
    {
        $server = $_SERVER['SERVER_NAME'];
        return <<<HTML
            <div style="padding: 3em">
                <script async 
                    src="https://telegram.org/js/telegram-widget.js?15" 
                    data-telegram-login="{$this->bot_name}" 
                    data-size="large" 
                    data-auth-url="https://{$_SERVER['SERVER_NAME']}/teleauth.php" 
                    data-request-access="write">
                </script>
            </div>
            HTML;
    }
}