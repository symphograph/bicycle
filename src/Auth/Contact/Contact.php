<?php

namespace Symphograph\Bicycle\Auth\Contact;

use Symphograph\Bicycle\DTO\BindTrait;

class Contact
{
    use BindTrait;

    public string $type;
    public string $value;

}