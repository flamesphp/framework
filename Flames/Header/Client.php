<?php

namespace Flames\Header;

use Exception;
use Flames\Collection\Arr;
use Flames\Js;

/**
 * Class Header represents a utility class for managing HTTP headers.
 */
class Client
{
    /**
     * Sets the value for a given key in the header array.
     *
     * @param string $key The key to set the value for.
     * @param mixed $value The value to be set.
     *
     * @return void
     */
    public static function set(string $key, mixed $value)
    {
        throw new Exception('Function unsupported on client side.');
    }

    /**
     * Retrieves the value associated with the given key from the header array.
     *
     * @param string $key The key of the value to retrieve.
     *
     * @return mixed|null The value associated with the key, or null if the key does not exist in the header array.
     */
    public static function get(string $key) : mixed
    {
        throw new Exception('Function unsupported on client side.');
    }

    /**
     * Retrieves all items from the data array.
     *
     * @return Arr The array containing all items.
     */
    public static function getAll() : Arr
    {
        throw new Exception('Function unsupported on client side.');
    }

    /**
     * Clears the data array.
     *
     * @return void
     */
    public static function clear() : void
    {
        throw new Exception('Function unsupported on client side.');
    }

    /**
     * Sends HTTP response headers based on the data array.
     *
     * Then it iterates through each key-value pair in the header array
     * and sends the HTTP response header by concatenating the key and value with a colon separator.
     *
     * @return void
     */
    public static function send()
    {
        throw new Exception('Function unsupported on client side.');
    }

    public static function redirect(string $url)
    {
        if (self::isAsyncRedirect() === true) {
            \Flames\Browser\Page::load($url);
        } else {
            Js::getWindow()->location = $url;
        }

        return Arr(['redirect' => $url]);
    }

    protected static $asyncRedirect = null;
    protected static function isAsyncRedirect()
    {
        if (self::$asyncRedirect === null) {
            self::$asyncRedirect = (Js::getWindow()->Flames->Internal->asyncRedirect === true);
        }
        return self::$asyncRedirect;
    }
}