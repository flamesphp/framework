<?php
declare(strict_types=1);


namespace Flames;

/**
 * @deprecated Use Flames\Framework\Header instead.
 */
class Header
{
    public static function set(string $key, mixed $value): void
    {
        \Flames\Framework\Header::set($key, $value);
    }

    public static function get(string $key): mixed
    {
        return \Flames\Framework\Header::get($key);
    }

    public static function getAll(): \Flames\Collection\Arr
    {
        return \Flames\Framework\Header::getAll();
    }

    public static function clear(): void
    {
        \Flames\Framework\Header::clear();
    }

    public static function send(): void
    {
        \Flames\Framework\Header::send();
    }

    public static function redirect(string $url, bool $sendNow = false): \Flames\Collection\Arr|string
    {
        return \Flames\Framework\Header::redirect($url, $sendNow);
    }
}
