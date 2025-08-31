<?php

namespace Symphograph\Bicycle\Contact;

enum ContactType: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Telegram = 'telegram';
    case Discord = 'discord';
    case Vkontakte = 'vkontakte';
}
