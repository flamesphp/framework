<?php

namespace Flames\Date\TimeZone;

use DateTimeZone;
use Flames\Environment;
use Flames\Js;

/**
 * @internal
 */
class Client extends DateTimeZone
{
    protected static $defaultTimezone = null;

    public static function getDefault()
    {
        if (self::$defaultTimezone !== null) {
            return self::$defaultTimezone;
        }

        $timezone = Js::getWindow()->Flames->Internal->dateTimeZone;
        if ($timezone === null || $timezone !== '') {
            $timezone = 'UTC';
        }

        self::$defaultTimezone = new self($timezone);
        return self::$defaultTimezone;
    }

    public static function getUtc()
    {
        return new self('UTC');
    }
}