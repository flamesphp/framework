<?php

namespace Flames\Connection;


class Async
{
    protected static $_async = null;
    protected static $_client = null;

    public static function isAsync(): bool
    {
        if (self::$_async !== null) {
            return self::$_async;
        }

        $headers = (function_exists('getallheaders') ? getallheaders() : null);

        if (isset($headers['X-Flames-Request']) === true && $headers['X-Flames-Request'] === 'async') {
            self::$_async = true;
            return self::$_async;
        }
        if (isset($headers['x-flames-request']) === true && $headers['x-flames-request'] === 'async') {
            self::$_async = true;
            return self::$_async;
        }

        self::$_async = false;
        return self::$_async;
    }

    public static function isFlamesClient(): bool
    {
        $headers = (function_exists('getallheaders') ? getallheaders() : null);

        if (isset($headers['X-Flames-Request']) === true && $headers['X-Flames-Request'] === 'client') {
            self::$_client = true;
            return self::$_client;
        }
        if (isset($headers['x-flames-request']) === true && $headers['x-flames-request'] === 'client') {
            self::$_client = true;
            return self::$_client;
        }

        self::$_client = false;
        return self::$_client;
    }
}