<?php

namespace Flames\Orm\Database\Cast\Meilisearch;

use Flames\Date\TimeZone;

class Datetime
{
    public static function pre($column, $value)
    {
        if ($column->nullable === true && $value === null) {
            return null;
        }

        if ($value === null) {
            $value = new \Flames\DateTimeImmutable();
        }

        $value = $value->setTimezone(TimeZone::getUtc());
        $value = $value->format('Y-m-d H:i:s.u');

        return $value;
    }

    public static function pos($column, $value, $fromDb = false)
    {
        if ($column->nullable === true && $value === null) {
            return null;
        }

        if ($value instanceof \Flames\DateTime || $value instanceof \Flames\DateTimeImmutable) {
            return $value;
        }
        if ($value instanceof \DateTimeImmutable) {
            return new \Flames\DateTimeImmutable($value->format('Y-m-d H:i:s.u'), $value->getTimezone());
        }
        if ($value instanceof \DateTime) {
            return new \Flames\DateTimeImmutable($value->format('Y-m-d H:i:s.u'), $value->getTimezone());
        }
        if (is_string($value) === true) {
            if ($fromDb === true) {
                $value = new \Flames\DateTimeImmutable($value, TimeZone::getUtc());
                $value = $value->setTimezone(TimeZone::getDefault());
            }

            return new \Flames\DateTimeImmutable($value);
        }
        if (is_int($value) === true) {
            if ($fromDb === true) {
                $value = new \Flames\DateTimeImmutable($value, TimeZone::getUtc());
                $value = $value->setTimezone(TimeZone::getDefault());
            }

            return (new \Flames\DateTimeImmutable($value))->setTimestamp($value);
        }

        return new \Flames\DateTimeImmutable();
    }
}