<?php

namespace Flames;

use Flames\Collection\Arr;

class Cookie
{
    public static function set(string $key, mixed $value, ?int $expireTimestamp = null): void
    {
        if ($expireTimestamp === null) {
            $expireTimestamp = (time() + 30758400);
        }

        setcookie($key, $value, $expireTimestamp, '/');
    }

    public static function get(string $key): string|null
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
        return null;
    }

    public static function remove(string $key): void
    {
        if (isset($_COOKIE[$key]) === false) {
            return;
        }

        setcookie($key, '', -1);
    }

    public static function getAll(): Arr
    {
        return Arr($_COOKIE);
    }
}