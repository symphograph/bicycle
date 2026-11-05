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

    public static function toDT(string $dateSTR, DTFormat $format = self::micro): DateTime
    {
        $formats = match ($format) {
            self::micro => [self::micro->value, self::sql->value],
            self::sql   => [self::sql->value,   self::micro->value],
            default     => [$format->value],
        };

        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $dateSTR);
            if ($dt) return $dt;
        }

        throw new \InvalidArgumentException(
            "Can not parse $dateSTR by format {$format->value}"
        );
    }

    public static function nowSql(): string
    {
        return date(DTF::sql->value);
    }

    public static function nowMicro(): string
    {
        [$usec, $sec] = explode(' ', microtime());     // "0.12345600" "1698765432"
        return gmdate('Y-m-d H:i:s', (int)$sec) . substr($usec, 1, 7);
    }


    /** 2037-12-31 23:59:59 */
    public static function maxSql(): string
    {
        return '2037-12-31 23:59:59';
    }

    /**
     * 2037-12-31 23:59:59.000000
     * Для совместимости с TimeStamp
     */
    public static function maxSqlMicro(): string
    {
        return '2037-12-31 23:59:59.000000';
    }

    public static function dtSql(DateTime $dt): string
    {
        return $dt->format(DTF::sql->value);
    }

    public static function dtSqlMicro(DateTime $dt): string
    {
        return $dt->format('Y-m-d H:i:s.u');
    }

    public static function fromMicro(int $micro): DateTime
    {
        $mul = 1_000_000;
        $sec  = intdiv($micro, $mul);
        $frac = $micro % $mul;

        $dt = DateTime::createFromFormat(
            'U.u',
            sprintf('%d.%06d', $sec, $frac),
            new \DateTimeZone('UTC')
        );

        if (!$dt) {
            throw new \InvalidArgumentException("Invalid microtimestamp: $micro");
        }

        return $dt;
    }
}
