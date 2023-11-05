<?php

namespace Symphograph\Bicycle\DTO;

use Symphograph\Bicycle\ITF\SocialAccountITF;

abstract class SocialAccountDTO implements SocialAccountITF
{
    public int $accountId;
}