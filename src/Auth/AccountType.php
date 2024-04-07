<?php

namespace Symphograph\Bicycle\Auth;

enum AccountType: string
{
    case Telegram = 'telegram';
    case Discord = 'discord';
    case Mailru = 'mailru';
    case VKontakte = 'vkontakte';
    case Default = 'default';
}
