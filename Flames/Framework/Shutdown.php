<?php
declare(strict_types=1);


namespace Flames\Framework;

/**
 * @internal
 */
class Shutdown
{
    private static bool $closed = false;

    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        register_shutdown_function([self::class, 'onShutdown']);
    }

    public static function onShutdown(): void
    {
        if (self::$closed) {
            return;
        }

        self::$closed = true;

        self::closeMemoryConnections();
        self::closeFileDbConnections();
        self::closeRedisConnections();
        self::closeDatabaseConnections();
    }

    private static function closeFileDbConnections(): void
    {
        if (!class_exists(\Flames\FileDb\Pool::class)) {
            return;
        }

        try {
            \Flames\FileDb\Pool::closeAll();
        } catch (\Throwable) {}
    }

    private static function closeMemoryConnections(): void
    {
        if (!class_exists(\Flames\Memory\Memory::class)) {
            return;
        }

        try {
            \Flames\Memory\Memory::close();
        } catch (\Throwable) {}
    }

    private static function closeRedisConnections(): void
    {
        if (!class_exists(\Flames\Redis\Pool::class)) {
            return;
        }

        try {
            \Flames\Redis\Pool::closeAll();
        } catch (\Throwable) {}
    }

    private static function closeDatabaseConnections(): void
    {
        if (class_exists(\Flames\Orm\Database\Driver::class)) {
            try {
                \Flames\Orm\Database\Driver::closeAll();
            } catch (\Throwable) {}
        }

        if (class_exists(\Flames\Orm\Database\RawConnection::class)) {
            try {
                \Flames\Orm\Database\RawConnection::closeAll();
            } catch (\Throwable) {}
        }
    }
}
