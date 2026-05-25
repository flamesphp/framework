<?php

namespace Flames;

use NumberFormatter;

class Money
{
    public static function format(int $value, ?string $localeIso = 'en_US', ?string $currency = 'USD')
    {
        if ($localeIso === null) {
            $localeIso = 'en_US';
        }
        if ($currency === null) {
            $currency = 'USD';
        }

        $formatter = new NumberFormatter( $localeIso, NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($value / 100, $currency);
    }
}