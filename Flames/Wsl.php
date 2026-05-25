<?php

namespace Flames;

/**
 * Wsl class provides a static method to determine whether the code is running within a Windows Subsystem Linux.
 */
class Wsl
{
    private static bool|null $isWsl = null;
    private static bool|null $isWsl2 = null;

    /**
     * Determines whether the code is running within a Windows Subsystem Linux.
     *
     * @return bool Returns true if the code is running within a Windows Subsystem Linux, false otherwise.
     */
    public static function isWsl() : bool
    {
        if (self::$isWsl !== null) {
            return self::$isWsl;
        }

        if (Os::isUnix() === false) {
            self::$isWsl = false;
            return self::$isWsl;
        }

        $hostname = shell_exec('hostnamectl');
        if ($hostname !== null) {
            $hostname = strtolower($hostname);

            if (str_contains($hostname, 'virtualization: wsl') === true) {
                self::$isWsl = true;
                return self::$isWsl;
            }
        }

        self::$isWsl = self::detectWsl2($hostname);
        return self::$isWsl;
    }

    /**
     * Determines whether the code is running within a Windows Subsystem Linux v2.
     *
     * @return bool Returns true if the code is running within a Windows Subsystem Linux v2, false otherwise.
     */
    public static function isWsl2() : bool
    {
        if (self::$isWsl2 !== null) {
            return self::$isWsl2;
        }

        if (Os::isUnix() === false) {
            self::$isWsl2 = false;
            return self::$isWsl2;
        }

        $hostname = shell_exec('hostnamectl');
        if ($hostname !== null) {
            $hostname = strtolower($hostname);
        }

        self::$isWsl2 = self::detectWsl2($hostname);
        return self::$isWsl2;
    }

    /**
     * Internal determines whether the code is running within a Windows Subsystem Linux v2.
     *
     * @return bool Returns true if the code is running within a Windows Subsystem Linux v2, false otherwise.
     */
    protected static function detectWsl2(string|null $hostname) : bool
    {
        if ($hostname === null) {
            return false;
        }

        $split = explode("\n", $hostname);
        foreach ($split as $line) {
            if (str_contains($line, 'kernel:') === true) {
                $line = trim($line);
                if (str_ends_with($line, '-wsl2') === true) {
                    return true;
                }
                break;
            }
        }

        return false;
    }
}