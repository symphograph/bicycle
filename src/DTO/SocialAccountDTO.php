<?php

namespace Symphograph\Bicycle\DTO;

use Symphograph\Bicycle\ITF\SocialAccountITF;

class SocialAccountDTO extends DTO implements SocialAccountITF
{
    public int $accountId;
}