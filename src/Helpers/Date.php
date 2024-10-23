<?php

namespace Symphograph\Bicycle\Helpers;

use DateTime;

class Date
{
    const array formats = [
        'Y-m-d',
        'd.m.Y',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'd.m.Y H:i',
        'd-m-Y',
        'Y/m/d',
        'd/m/Y',
        'Y.m.d',
        'Ymd',
        'H:i:s',
        'i:s',
        'Y-m-d H:i:sP',
        'Y-m-d H:i:sO',
        'Y-m-d H:i:s.u',
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:sO',
        'Y-m-d\TH:i:s.u',
        'Y-m-d\TH:i:s.uP',
        'Y-m-d\TH:i:s.uO',
        'c', // ISO 8601 формат
        'r', // RFC 2822 формат
        'U'
    ];

    /**
     *  Format a time/date from any format to any format
     * @param string $outputFormat <p>
     *  The format of the outputted date string. See the formatting
     *  options below.
     *  </p>
     *  <p>
     *  <br>
     *  The following characters are recognized in the
     *  format parameter string:
     *  <br><br>
     *  <table>
     *  <tr valign="top" colspan="3" bgcolor="silver">
     *  <th>format character</th>
     *  <th>Description</th>
     *  <th>Example returned values</th>
     *  </tr>
     *  <tr valign="top">
     *  <td><b>Day</b></td>
     *  <td>---</td>
     *  <td>---</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>d</td>
     *  <td>Day of the month, 2 digits with leading zeros</td>
     *  <td>01 to 31</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>D</td>
     *  <td>A textual representation of a day, three letters</td>
     *  <td>Mon through Sun</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>j</td>
     *  <td>Day of the month without leading zeros</td>
     *  <td>1 to 31</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>l (lowercase 'L')</td>
     *  <td>A full textual representation of the day of the week</td>
     *  <td>Sunday through Saturday</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>N</td>
     *  <td>ISO-8601 numeric representation of the day of the week (added in
     *  PHP 5.1.0)</td>
     *  <td>1 (for Monday) through 7 (for Sunday)</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>S</td>
     *  <td>English ordinal suffix for the day of the month, 2 characters</td>
     *  <td>
     *  st, nd, rd or
     *  th. Works well with j
     *  </td>
     *  </tr>
     *  <tr valign="top">
     *  <td>w</td>
     *  <td>Numeric representation of the day of the week</td>
     *  <td>0 (for Sunday) through 6 (for Saturday)</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>z</td>
     *  <td>The day of the year (starting from 0)</td>
     *  <td>0 through 365</td>
     *  </tr>
     *  <tr valign="top">
     *  <td><b>Week</b></td>
     *  <td>---</td>
     *  <td>---</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>W</td>
     *  <td>ISO-8601 week number of year, weeks starting on Monday (added in PHP 4.1.0)</td>
     *  <td>Example: 42 (the 42nd week in the year)</td>
     *  </tr>
     *  <tr valign="top">
     *  <td><b>Month</b></td>
     *  <td>---</td>
     *  <td>---</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>F</td>
     *  <td>A full textual representation of a month, such as January or March</td>
     *  <td>January through December</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>m</td>
     *  <td>Numeric representation of a month, with leading zeros</td>
     *  <td>01 through 12</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>M</td>
     *  <td>A short textual representation of a month, three letters</td>
     *  <td>Jan through Dec</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>n</td>
     *  <td>Numeric representation of a month, without leading zeros</td>
     *  <td>1 through 12</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>t</td>
     *  <td>Number of days in the given month</td>
     *  <td>28 through 31</td>
     *  </tr>
     *  <tr valign="top">
     *  <td><b>Year</b></td>
     *  <td>---</td>
     *  <td>---</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>L</td>
     *  <td>Whether it's a leap year</td>
     *  <td>1 if it is a leap year, 0 otherwise.</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>o</td>
     *  <td>ISO-8601 year number. This has the same value as
     *  Y, except that if the ISO week number
     *  (W) belongs to the previous or next year, that year
     *  is used instead. (added in PHP 5.1.0)</td>
     *  <td>Examples: 1999 or 2003</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>Y</td>
     *  <td>A full numeric representation of a year, 4 digits</td>
     *  <td>Examples: 1999 or 2003</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>y</td>
     *  <td>A two digit representation of a year</td>
     *  <td>Examples: 99 or 03</td>
     *  </tr>
     *  <tr valign="top">
     *  <td><b>Time</b></td>
     *  <td>---</td>
     *  <td>---</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>a</td>
     *  <td>Lowercase Ante meridiem and Post meridiem</td>
     *  <td>am or pm</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>A</td>
     *  <td>Uppercase Ante meridiem and Post meridiem</td>
     *  <td>AM or PM</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>B</td>
     *  <td>Swatch Internet time</td>
     *  <td>000 through 999</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>g</td>
     *  <td>12-hour format of an hour without leading zeros</td>
     *  <td>1 through 12</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>G</td>
     *  <td>24-hour format of an hour without leading zeros</td>
     *  <td>0 through 23</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>h</td>
     *  <td>12-hour format of an hour with leading zeros</td>
     *  <td>01 through 12</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>H</td>
     *  <td>24-hour format of an hour with leading zeros</td>
     *  <td>00 through 23</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>i</td>
     *  <td>Minutes with leading zeros</td>
     *  <td>00 to 59</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>s</td>
     *  <td>Seconds, with leading zeros</td>
     *  <td>00 through 59</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>u</td>
     *  <td>Microseconds (added in PHP 5.2.2)</td>
     *  <td>Example: 654321</td>
     *  </tr>
     *  <tr valign="top">
     *  <td><b>Timezone</b></td>
     *  <td>---</td>
     *  <td>---</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>e</td>
     *  <td>Timezone identifier (added in PHP 5.1.0)</td>
     *  <td>Examples: UTC, GMT, Atlantic/Azores</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>I (capital i)</td>
     *  <td>Whether or not the date is in daylight saving time</td>
     *  <td>1 if Daylight Saving Time, 0 otherwise.</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>O</td>
     *  <td>Difference to Greenwich time (GMT) in hours</td>
     *  <td>Example: +0200</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>P</td>
     *  <td>Difference to Greenwich time (GMT) with colon between hours and minutes (added in PHP 5.1.3)</td>
     *  <td>Example: +02:00</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>T</td>
     *  <td>Timezone abbreviation</td>
     *  <td>Examples: EST, MDT ...</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>Z</td>
     *  <td>Timezone offset in seconds. The offset for timezones west of UTC is always
     *  negative, and for those east of UTC is always positive.</td>
     *  <td>-43200 through 50400</td>
     *  </tr>
     *  <tr valign="top">
     *  <td><b>Full Date/Time</b></td>
     *  <td>---</td>
     *  <td>---</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>c</td>
     *  <td>ISO 8601 date (added in PHP 5)</td>
     *  <td>2004-02-12T15:19:21+00:00</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>r</td>
     *  <td>RFC 2822 formatted date</td>
     *  <td>Example: Thu, 21 Dec 2000 16:01:07 +0200</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>U</td>
     *  <td>Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)</td>
     *  <td>See also time</td>
     *  </tr>
     *  </table>
     *  </p>
     * @param string $inputDate
     * @return string|false a formatted date string.
     * If $inputDate have invalid format return false
     * /
     * @return false|string
     */
    public static function dateFormatFeel(string $inputDate, string $outputFormat = 'Y-m-d H:i:s'): false|string
    {
        if(empty($outputFormat)){
            $outputFormat = 'Y-m-d H:i:s';
        }

        foreach (self::formats as $format) {
            $date = date_create_from_format($format, $inputDate);

            if ($date !== false) {
                return date_format($date, $outputFormat);
            }
        }

        return false;
    }

    public static function extractDate(string $inputString, ?array $formats = null): ?string
    {
        if(empty($formats)) {
            $formats = ['Y-m-d', 'd-m-Y', 'Y.m.d', 'd.m.Y'];
        }
        $dateRegex = '/(\d{1,4}[._-]\d{1,2}[._-]\d{2,4})/';
        preg_match_all($dateRegex, $inputString, $matches);

        foreach ($matches[1] as $match) {
            if (self::isDate($match, $formats)) {
                return $match;
            }
        }

        return null;
    }

    public static function isDate(string $date, string|array|null $formats = null): bool
    {
        if(empty($formats)) {
            $formats = self::formats;
        }elseif (is_string($formats)) {
            $formats = [$formats];
        }

        foreach ($formats as $format){
            $dateTime = DateTime::createFromFormat($format, $date);
            if(!$dateTime) continue;
            $errors = DateTime::getLastErrors();
            return empty($errors);
        }
        return false;
    }

    public static function yesterday(string $date = 'Now', string $outputFormat = 'Y-m-d'): string
    {
        if($date !== 'Now'){
            $date = self::extractDate($date);
        }
        $date = self::extractDate($date);
        return (new DateTime($date))->modify('-1 day')->format($outputFormat);
    }

}

