<?php
namespace Symphograph\Bicycle\Auth\Telegram;
use Symphograph\Bicycle\DB;
use Symphograph\Bicycle\DTO\DTOTrait;
use Symphograph\Bicycle\DTO\SocialAccountDTO;

class TeleUserDTO extends SocialAccountDTO
{
    use DTOTrait;
    const tableName = 'user_telegram';
    public int    $id         = 0;
    public string $first_name = '';
    public string $last_name  = '';
    public string $username   = '';
    public string $photo_url  = '';
    public string $auth_date  = '';

}