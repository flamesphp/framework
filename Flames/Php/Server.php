<?php

/**
 * The Server class provides methods for checking if the code is running on a server environment
 * and for running a local development server.
 */

namespace Flames\Php;

class Server
{
    private static $isServer = null;

    /**
     * Checks if the code is running on a server environment.
     *
     * @return bool Returns true if the code is running on a server, false otherwise.
     */
    public static function isServer() : bool
    {
        if (self::$isServer !== null) {
            return self::$isServer;
        }

        if (isset($_SERVER['SERVER_SOFTWARE']) === false) {
            self::$isServer = false;
            return self::$isServer;
        }

        self::$isServer = (str_contains($_SERVER['SERVER_SOFTWARE'], 'Development Server') === true);
        return self::$isServer;
    }

    /**
     * Run a simple PHP development server
     *
     * @return bool Returns true if the code is running on a server, false otherwise.
     */
    public static function run(string $host = '0.0.0.0', int $port = 80) : bool
    {
        $uri = ($host . ':' . $port);
        exec('php -S ' . $uri . ' index.php');
        return false;
    }
}