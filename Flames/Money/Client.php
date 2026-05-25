<?php

namespace Flames\Money;

/**
 * @internal
 */
class Client
{
    public static function format(string $value, ?string $localeIso = 'en_US', ?string $currency = 'USD')
    {
        if ($localeIso === null) {
            $localeIso = 'en_US';
        }
        if ($currency === null) {
            $currency = 'USD';
        }

        $decimal = '.';
        $million = ',';
        $currencySymbol = '$ ';

        if ($localeIso === 'pt_BR') {
            $decimal = ',';
            $million = '.';
            $currencySymbol = 'R$ ';
        }

        $valueLen = strlen($value);
        if ($valueLen === 2) {
            return ($currencySymbol . '0' . $decimal . $value);
        }
        if ($valueLen === 1) {
            return ($currencySymbol . '0' . $decimal . '0' . $value);
        }

        $mount = ($decimal . substr($value, $valueLen -2));
        $diff = strrev(substr($value, 0, $valueLen -2));

        $preMount = '';
        $split = array_reverse(str_split($diff, 3));
        foreach ($split as $part) {
            $preMount .= (strrev($part) . $million);
        }
        if ($preMount !== '') {
            $preMount = substr($preMount, 0, -1);
        }

        return ($currencySymbol . $preMount . $mount);
    }
}