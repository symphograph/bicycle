<?php

namespace Symphograph\Bicycle\Helpers;

enum DTFormat: string
{
    case Y = 'Y';
    case y = 'y';

    case dSql = 'Y-m-d';           // DATE
    case dtSql = 'Y-m-d H:i:s';    // DATETIME
    case dtSqlMin = 'Y-m-d H:i';

    case dmYDots = 'd.m.Y';
    case dmYDash = 'd-m-Y';
    case YmdSlash = 'Y/m/d';
    case dmYEuroSlash = 'd/m/Y';
    case YmdDots = 'Y.m.d';
    case Ymd = 'Ymd';

    case tHis = 'H:i:s';
    case tis = 'i:s';

    case dtTzColon = 'Y-m-d H:i:sP';
    case dtTzOffset = 'Y-m-d H:i:sO';
    case dtMicro = 'Y-m-d H:i:s.u';
    case dtIso = 'Y-m-d\TH:i:sP';
    case dtIsoOffset = 'Y-m-d\TH:i:sO';
    case dtIsoMicro = 'Y-m-d\TH:i:s.u';
    case dtIsoMicroTz = 'Y-m-d\TH:i:s.uP';
    case dtIsoMicroOffset = 'Y-m-d\TH:i:s.uO';

    case iso8601 = 'c';
    case rfc2822 = 'r';
    case u = 'U';
}