<?php

namespace Flames;

use Flames\Date\TimeZone;

class DateTimeImmutable extends \DateTimeImmutable
{
    public function __construct(\Flames\DateTime|\Flames\DateTimeImmutable|\DateTime|\DateTimeImmutable|string|int|null $dateTime = 'now', \Flames\Date\TimeZone|\DateTimeZone|null $timezone = null)
    {
        if ($timezone === null) {
            $timezone = TimeZone::getDefault();
        }

        if (is_string($dateTime)) {
            parent::__construct($dateTime, $timezone);
        } elseif (is_int($dateTime)) {
            parent::__construct('now', $timezone);
            $this->setTimestamp($dateTime);
        } elseif ($dateTime instanceof \Flames\DateTime || $dateTime instanceof \Flames\DateTimeImmutable || $dateTime instanceof \DateTime || $dateTime instanceof \DateTimeImmutable) {
            parent::__construct($dateTime->format('Y-m-d H:i:s.u'), $dateTime->getTimezone());
        } else {
            parent::__construct('now', $timezone);
        }
    }

    public function __toString()
    {
        return $this->format('Y-m-d H:i:s');
    }
}