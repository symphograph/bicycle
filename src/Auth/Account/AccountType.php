<?php

namespace Symphograph\Bicycle\Auth\Account;

enum AccountType: string
{
    case Telegram = 'telegram';
    case Discord = 'discord';
    case Mailru = 'mailru';
    case VKontakte = 'vkontakte';
    case Email = 'email';
    case Default = 'default';
    case Yandex = 'yandex';
}
