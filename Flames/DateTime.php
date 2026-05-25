<?php

namespace Flames;

class DateTime extends \DateTime
{
    public function __construct(\Flames\DateTime|\Flames\DateTimeImmutable|\DateTime|\DateTimeImmutable|string|int|null $dateTime = null, \Flames\Date\TimeZone|\DateTimeZone|null $timezone = null)
    {
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

    public function __toString(): string
    {
        return $this->format();
    }

    public function format(string|null $format = null): string
    {
        if ($format === null) {
            $format = 'Y-m-d H:i:s';
        }
        return parent::format($format);
    }

    public static function now()
    {
        return new DateTime('now');
    }
}