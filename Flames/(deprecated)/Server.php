<?php
declare(strict_types=1);



namespace Flames;

class Server
{
    public static function getUri($withQueryString = false): string
    {
        if ($withQueryString === true) {
            return $_SERVER['REQUEST_URI'];
        }

        return explode('?', $_SERVER['REQUEST_URI'])[0];
    }

    protected static $_uniqueId = null;

    public static function getUniqueId()
    {
        if (self::$_uniqueId !== null) {
            return self::$_uniqueId;
        }

        $uniqueIdKey = sha1(Environment::get('APP_KEY') . '.uniqueid');
        $uniqueId = null;
        try {
            $uniqueId = (string)file_get_contents(ROOT_PATH . '.storage/' . $uniqueIdKey . '.blob');
        } catch (\Exception) {}

        if ($uniqueId !== '' && $uniqueId !== null) {
            self::$_uniqueId = $uniqueId;
            return self::$_uniqueId;
        }

        $uniqueId = sha1(uniqid() . random_int(0, PHP_INT_MAX) . random_int(0, PHP_INT_MAX));
        try {
            file_put_contents(ROOT_PATH . '.storage/' . $uniqueIdKey . '.blob', $uniqueId);
        } catch (\Exception) {
            if (is_dir(ROOT_PATH . '.storage/') === false) {
                $mask = umask(0);
                mkdir(ROOT_PATH . '.storage/', 0777, true);
                umask($mask);
            }
            file_put_contents(ROOT_PATH . '.storage/' . $uniqueIdKey . '.blob', $uniqueId);
        }

        self::$_uniqueId = $uniqueId;
        return self::$_uniqueId;
    }
}