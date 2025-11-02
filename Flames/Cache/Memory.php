<?php

namespace Flames\Cache;

class Memory
{
    public static function get(string $key): mixed
    {
        return null;
    }

    public static function set(string $key, mixed $value, ?int $expireSeconds = null): void
    {
    }

    public static function remove(string $key): void
    {
    }

    public static function truncate(): void
    {
    }
}