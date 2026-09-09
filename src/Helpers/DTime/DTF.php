<?php

namespace Symphograph\Bicycle\Helpers\DTime;

use DateTime;

enum DTF: string implements DTFormat
{
    case sql = 'Y-m-d H:i:s';     // SQL DATETIME
    case sqlMin = 'Y-m-d H:i';

    case tzColon = 'Y-m-d H:i:sP';
    case tzOffset = 'Y-m-d H:i:sO';
    case micro = 'Y-m-d H:i:s.u';
    case iso = 'Y-m-d\TH:i:sP';
    case isoOffset = 'Y-m-d\TH:i:sO';
    case isoMicro = 'Y-m-d\TH:i:s.u';
    case isoMicroTz = 'Y-m-d\TH:i:s.uP';
    case isoMicroOffset = 'Y-m-d\TH:i:s.uO';

    case iso8601 = 'c';
    case rfc2822 = 'r';
    case u = 'U';                 // Unix timestamp

    public static function toDT(string $dateSTR, DTFormat $format = self::sql): DateTime
    {
        return DateTime::createFromFormat($format->value, $dateSTR);
    }

    public static function nowSql(): string
    {
        return date(DTF::sql->value);
    }
}
