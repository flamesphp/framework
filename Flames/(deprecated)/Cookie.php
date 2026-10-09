<?php
declare(strict_types=1);


namespace Flames;

use Flames\Collection\Arr;

class Cookie
{
    public static function set(string $key, mixed $value, ?int $expireTimestamp = null): void
    {
        $expireTimestamp ??= time() + 30758400;

        setcookie($key, $value, ['expires' => $expireTimestamp, 'path' => '/']);
    }

    public static function get(string $key): string|null
    {
        return $_COOKIE[$key] ?? null;
    }

    public static function remove(string $key): void
    {
        if (isset($_COOKIE[$key]) === false) {
            return;
        }

        setcookie($key, '', ['expires' => -1]);
    }

    public static function getAll(): Arr
    {
        return Arr($_COOKIE);
    }
}