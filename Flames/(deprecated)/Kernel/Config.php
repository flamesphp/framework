<?php
declare(strict_types=1);


namespace Flames\Kernel;

/**
 * @internal
 */
final class Config
{
    protected static mixed $data = null;

    public static function get(): mixed
    {
        return self::$data;
    }

    public static function set(mixed $data): void
    {
        self::$data = $data;
    }
}
