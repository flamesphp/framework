<?php
declare(strict_types=1);



namespace Flames\Server;

/**
 * The Os class provides methods for determining the operating system.
 */
class Os
{
    const UNKNOWN = 'Unknown';
    const WINDOWS = 'Windows';
    const BSD = 'BSD';
    const DARWIN = 'Darwin';
    const SOLARIS = 'Solaris';
    const LINUX = 'Linux';

    /**
     * Returns the name of the operating system.
     *
     * @return string The name of the operating system.
     */
    public static function getName() : string
    {
        return PHP_OS_FAMILY;
    }

    /**
     * Checks if the current operating system is Unix-like.
     *
     * @return bool Returns true if the current operating system is Unix-like, false otherwise.
     */
    public static function isUnix() : bool
    {
        return (!self::isWindows() && self::getName() !== self::UNKNOWN);
    }

    public static function isLinux() : bool
    {
        return (self::getName() === self::LINUX);
    }

    /**
     * Checks if the current operating system is Windows.
     *
     * @return bool Returns true if the current operating system is Windows, false otherwise.
     */
    public static function isWindows() : bool
    {
        return (self::getName() === self::WINDOWS);
    }

    public static function isBsd() : bool
    {
        return (self::getName() === self::BSD);
    }

    public static function isSolaris() : bool
    {
        return (self::getName() === self::SOLARIS);
    }

    public static function isDarwin() : bool
    {
        return (self::getName() === self::DARWIN);
    }

    public static function isMacintosh() : bool
    {
        return self::isDarwin();
    }
}