<?php

namespace Flames\Kernel\Client
{
    /**
     * @internal
     */
    final class Virtual
    {
        private static $buffers = [];

        private static $constructors = [];

        private static $tags = [];

        private static $views = [];

        public static function load(string $class): bool
        {
            try {
                $classHash = sha1($class);
                if (isset(self::$buffers[$classHash]) === true) {
                    eval(base64_decode(self::$buffers[$classHash]));
                    unset(self::$buffers[$classHash]);
                    if (in_array($classHash, self::$constructors) === true) {
                        $class::__constructStatic();
                    }
                    return true;
                }
            } catch (\Exception|\Error $e) {
                Error::handler($e);
            }

            return false;
        }

        public static function getTagClass(string $tag)
        {
            if (isset(self::$tags[$tag]) === true) {
                return self::$tags[$tag];
            }

            return null;
        }

        public static function getViews()
        {
            return self::$views;
        }
    }
}
