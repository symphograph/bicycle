<?php
namespace Symphograph\Bicycle\Auth\Telegram;
class TeleUser
{
    public int $id = 0;
    public string $first_name = '';
    public string $last_name = '';
    public string $username = '';
    public string $photo_url = '';
    public string $auth_date = '';
    public string $hash = '';

    public function byCook()
    {
        if(isset($_COOKIE['tg_user'])) {
            $auth_data_json = urldecode($_COOKIE['tg_user']);
            $auth_data      = json_decode($auth_data_json);
            $auth_data = (object) $auth_data;
            foreach ($this as $k => $val){
                if(isset($auth_data->$k)){
                    $this->$k = $auth_data->$k;
                }
            }
            return $auth_data;
        }
        return false;
    }

    public static function byData(array|object $auth_data) : TeleUser
    {
        $TeleUser = new TeleUser();
        $auth_data = (object) $auth_data;
        foreach ($TeleUser as $k => $val){
            if(isset($auth_data->$k)){
                $TeleUser->$k = $auth_data->$k;
            }
        }
        return $TeleUser;
    }

}