<?php
declare(strict_types=1);


namespace Flames\Kernel\Client
{
    /**
     * @internal
     */
    final class Virtual
    {
        private static $buffers = [];

        private static $dependencies = [];

        private static $loaded = [];

        /** @var array<string, true> */
        private static $loading = [];

        private static $constructors = [];

        private static $tags = [];

        private static $views = [];

        public static function load(string $class): bool
        {
            try {
                $class = ltrim($class, '\\');

                if (isset(self::$loaded[$class]) === true) {
                    return true;
                }

                if (self::isDeclared($class) === true) {
                    self::$loaded[$class] = true;

                    return true;
                }

                if (isset(self::$loading[$class]) === true) {
                    return true;
                }

                self::$loading[$class] = true;

                $dependencies = self::$dependencies[$class] ?? [];
                if ($dependencies !== []) {
                    $dependencies = array_values(array_unique($dependencies));
                }

                foreach ($dependencies as $dependency) {
                    if (self::load($dependency) === false) {
                        unset(self::$loading[$class]);

                        return false;
                    }
                }

                if (self::isDeclared($class) === true) {
                    self::$loaded[$class] = true;
                    unset(self::$loading[$class]);

                    return true;
                }

                $classHash = sha1($class);
                if (isset(self::$buffers[$classHash]) === false) {
                    $mockHash = sha1(substr($class, 0, (int) strrpos($class, '\\')) ?: $class);
                    if ($mockHash !== $classHash && isset(self::$buffers[$mockHash]) === true) {
                        $classHash = $mockHash;
                    }
                }

                if (isset(self::$buffers[$classHash]) === true) {
                    if (self::isDeclared($class) === true) {
                        unset(self::$buffers[$classHash]);
                        self::$loaded[$class] = true;
                        unset(self::$loading[$class]);

                        return true;
                    }

                    eval(base64_decode(self::$buffers[$classHash]));
                    unset(self::$buffers[$classHash]);

                    if (self::isDeclared($class) === false) {
                        unset(self::$loading[$class]);

                        return false;
                    }

                    self::$loaded[$class] = true;
                    if (in_array($classHash, self::$constructors) === true) {
                        $class::__constructStatic();
                    }

                    unset(self::$loading[$class]);

                    return true;
                }

                unset(self::$loading[$class]);
            } catch (\Exception|\Error $e) {
                unset(self::$loading[$class]);
                Error::handler($e);
            }

            return false;
        }

        private static function isDeclared(string $class): bool
        {
            return class_exists($class, false)
                || interface_exists($class, false)
                || trait_exists($class, false);
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
