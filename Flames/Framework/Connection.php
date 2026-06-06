<?php
declare(strict_types=1);


namespace Flames\Framework;

/**
 * The Connection class is responsible for retrieving the client's IP address.
 */
final class Connection
{
    protected static string|null $currentIp = null;
    protected static string|null $currentIpProxy = null;

    /**
     * Retrieves the client IP address.
     *
     * @param bool $parseProxy Determines whether to parse the proxy IP address (default: false)
     * @return string|null The client IP address or null if it cannot be determined
     */
    public static function getIp(bool $parseProxy = false): string|null
    {
        if ($parseProxy === true) {
            if (self::$currentIpProxy !== null) {
                return self::$currentIpProxy;
            }

            $ip = null;

            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

            if ($ip !== null) {
                self::$currentIpProxy = $ip;
                return self::$currentIpProxy;
            }
        }

        if (self::$currentIp !== null) {
            return self::$currentIp;
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            self::$currentIp = $_SERVER['REMOTE_ADDR'];
            return self::$currentIp;
        }

        return null;
    }
}
