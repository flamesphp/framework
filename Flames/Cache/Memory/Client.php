<?php

namespace Flames\Cache\Memory;

use Flames\Js;

/**
 * @internal
 */
class Client
{
    protected static $localStorage = null;

    public static function get(string $key): mixed
    {
        $storage = self::getLocalStorage();
        $data = unserialize($storage->getItem($key));

        if ($data === false) {
            return null;
        }

        if ($data['e'] !== null) {
            $diffTime = (time() - $data['e']);
            if ($diffTime > 0) {
                self::remove($key);
                return null;
            }
        }

        return $data['v'];
    }

    public static function set(string $key, mixed $value, ?int $expireSeconds = null): void
    {
        $data = [
            'e' => (($expireSeconds === null)
                ? null
                : time() + $expireSeconds),
            'v' => $value,
        ];

        $storage = self::getLocalStorage();
        $storage->setItem($key, serialize($data));
    }

    public static function remove(string $key): void
    {
        $storage = self::getLocalStorage();
        $storage->removeItem($key);
    }

    public static function truncate(): void
    {
        $storage = self::getLocalStorage();
        $storage->clear();
    }

    protected static function getLocalStorage(): mixed
    {
        if (self::$localStorage !== null) {
            return self::$localStorage;
        }

        self::$localStorage = Js::getWindow()->localStorage;
        return self::$localStorage;
    }
}