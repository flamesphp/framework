<?php

namespace Flames\Php;

use Flames\Collection\Arr;
use Flames\Kernel;

class Extension
{
    public static function getAll(): Arr
    {
        $extensions = get_loaded_extensions();
        foreach ($extensions as $key => $extension) {
            $extension = strtolower($extension);
            if (str_contains($extension, ' ') === true) {
                $extension = str_replace(' ', '_', $extension);
            }
            $extensions[$key] = $extension;
        }

        return Arr($extensions);
    }

    public static function load(string $extension): bool
    {
        $extension = self::sanitize($extension);

        if (Kernel::MODULE === 'SERVER') {
            $prefix = ((PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '');
            return \dl($prefix . 'sqlite.' . PHP_SHLIB_SUFFIX);
        }

        // TODO: verify swf extension
        \dl($extension . '.so');
        usleep(1);

        return extension_loaded($extension);
    }

    public static function isLoaded(string $extension): bool
    {
        $extension = self::sanitize($extension);
        return extension_loaded($extension);
    }

    private static function sanitize(string $extension): string
    {
        $extension = strtolower($extension);
        if (str_starts_with($extension, 'php_')) {
            $extension = substr($extension, 4);
            if (str_ends_with($extension, '.dll')) {
                $extension = substr($extension, 0, -4);
            }
        } elseif(str_ends_with($extension, '.so')) {
            $extension = substr($extension, 0, -3);
        }
        return $extension;
    }
}
