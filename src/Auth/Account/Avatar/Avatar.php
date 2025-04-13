<?php

namespace Symphograph\Bicycle\Auth\Account\Avatar;

use Symphograph\Bicycle\Files\FileIMG;


class Avatar extends FileIMG
{
    private const string emptyAva  = '/img/avatars/init_ava.png';
    private const array censored  = ['df303c56aac75aed75398543cba7da4b'];

    public ?string $src      = '/img/avatars/init_ava.png';
    public ?string $fileName = 'init_ava.png';


}