<?php

namespace Flames\Kernel;

use Flames\Js;

/**
 * @internal
 */
final class Client
{
    public const VERSION = 'alpha1.19';
    public const MODULE  = 'CLIENT';

    private static bool $isNativeBuild = false;

    private static $data = null;
    private static $getData = false;


    public static function __getData() {
        if (self::$getData === false) {
            $flamesElement = Js::getWindow()->document->querySelector('flames');
            $data = base64_decode($flamesElement->innerHTML);
            try {
                $data = substr($data, strpos($data, '|') + 1);
                $data = substr($data, strpos($data, '|') + 1);
                $data = substr($data, strpos($data, '|') + 1);
                $data = unserialize($data);
            } catch (\Exception|\Error $_) {}
            if ($data === false) {
                $data = (object)[];
            }
            self::$data = $data;
            self::$getData = true;
            $flamesElement->remove();
        }
        return self::$data;
    }

    public static function __injectData($data)
    {
        $data = base64_decode($data);
        try {
            $data = substr($data, strpos($data, '|') + 1);
            $data = substr($data, strpos($data, '|') + 1);
            $data = substr($data, strpos($data, '|') + 1);
            $data = unserialize($data);
        } catch (\Exception|\Error $_) {}
        if ($data === false) {
            $data = (object)[];
        }
        self::$data = $data;
        self::$getData = true;
    }

    public static function __injector()
    { self::__getData(); }

    public static function isNativeBuild(): bool
    { return self::$isNativeBuild;  }

    public static function __setNativeBuild(bool $nativeBuild): bool
    { return self::$isNativeBuild = $nativeBuild;  }
}