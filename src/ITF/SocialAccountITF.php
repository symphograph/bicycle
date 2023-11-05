<?php

namespace Symphograph\Bicycle\ITF;

interface SocialAccountITF
{
    public static function byContact(string $contactValue): self|false;
}