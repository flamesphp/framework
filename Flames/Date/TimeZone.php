<?php

namespace Flames\Date;

use DateTimeZone;
use Flames\Environment;

class TimeZone extends DateTimeZone
{
    protected static $defaultTimezone = null;

    public static function getDefault()
    {
        if (self::$defaultTimezone !== null) {
            return self::$defaultTimezone;
        }

        $timezone = Environment::get('DATE_TIMEZONE');
        if ($timezone === null || $timezone === '') {
            $timezone = 'UTC';
        }

        self::$defaultTimezone = new TimeZone($timezone);
        return self::$defaultTimezone;
    }

    public static function getUtc()
    {
        return new TimeZone('UTC');
    }
}