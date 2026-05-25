<?php

namespace Flames;

/**
 * Docker class provides a static method to determine whether the code is running within a Docker container.
 */
class Docker
{
    private static $isDocker = null;

    /**
     * Determines whether the code is running within a Docker container.
     *
     * @return bool Returns true if the code is running within a Docker container, false otherwise.
     */
    public static function isDocker() : bool
    {
        if (self::$isDocker !== null) {
            return self::$isDocker;
        }

        if (Os::isUnix() === false) {
            self::$isDocker = false;
            return self::$isDocker;
        }

        self::$isDocker = file_exists('/.dockerenv');
        return self::$isDocker;
    }
}