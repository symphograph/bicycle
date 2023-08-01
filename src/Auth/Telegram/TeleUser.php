<?php
namespace Symphograph\Bicycle\Auth\Telegram;
use Symphograph\Bicycle\DB;

class TeleUser
{
    public int    $id         = 0;
    public int    $accountId  = 0;
    public string $first_name = '';
    public string $last_name  = '';
    public string $username   = '';
    public string $photo_url  = '';
    public string $auth_date  = '';
    public string $hash       = '';

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

    public static function byData(array|object $auth_data) : self
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

    public static function byId(int $id): self|bool
    {
        $qwe = qwe("select * from user_telegram where id = :id", ['id' => $id]);
        return $qwe->fetchObject(self::class);
    }

    public static function byAccountId(int $accountId): self|bool
    {
        $qwe = qwe("select * from user_telegram where accountId = :accountId", ['accountId' => $accountId]);
        return $qwe->fetchObject(self::class);
    }

    public function putToDB(): bool
    {
        $params = [
            'id'         => $this->id,
            'accountId'  => $this->accountId,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'username'   => $this->username,
            'photo_url'  => $this->photo_url,
            'auth_date'  => date('Y-m-d H:i:s',$this->auth_date)
        ];

        return DB::replace('user_telegram', $params);

    }

}