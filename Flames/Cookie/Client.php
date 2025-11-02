<?php

namespace Flames\Cookie;

use Flames\Collection\Arr;
use Flames\DateTime;
use Flames\Js;

/**
 * @internal
 */
class Client
{
    public static function set(string $key, mixed $value, ?int $expireTimestamp = null): void
    {
        if ($expireTimestamp === null) {
            $expireTimestamp = (time() + 30758400);
        }

        $date = new DateTime();
        $date->setTimezone(new \DateTimeZone('UTC'));
        $date->setTimestamp($expireTimestamp);

        $dowMap = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $dateUTCString = (
            $dowMap[$date->format('w')] .
            $date->format(', d M Y H:i:s') .
            ' UTC'
        );

        Js::getWindow()->document->cookie = ($key . '=' . rawurlencode($value) . '; expires=' . $dateUTCString . ';');
    }

    public static function get(string $key): string|null
    {
        $cookies = self::getAll();
        if ($cookies->containsKey($key)) {
            return $cookies[$key];
        }
        return null;
    }

    public static function remove(string $key): void
    {
        $cookies = self::getAll();
        if ($cookies->containsKey($key) === false) {
            return;
        }

        Js::getWindow()->document->cookie = ($key . '=; expires=Thu, 01 Jan 1970 00:00:01 UTC;');
    }

    public static function getAll(): Arr
    {
        $cookies = Arr();
        $localCookies = Js::getWindow()->document->cookie;
        $split = explode(';', $localCookies);

        foreach ($split as $part) {
            $part = trim($part);

            $indexOfSplit = strpos($part, '=');
            if ($indexOfSplit === false) {
                continue;
            }

            $key = substr($part, 0, $indexOfSplit);
            $value = rawurldecode(substr($part, $indexOfSplit + 1));

            $cookies[$key] = $value;
        }

        return $cookies;
    }

}