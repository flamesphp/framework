<?php
declare(strict_types=1);


namespace Flames;

use Error;

/**
 * Class Required
 *
 * The Required class provides methods for including PHP files and checking the existence of functions.
 */
class Required
{
    /**
     * _function
     *
     * Attempts to require a polyfill file based on the provided $name parameter.
     * If the function does not exist, it looks for the polyfill file in two possible locations:
     *     - ROOT_PATH/Flames/Kernel/Polyfill/{$name}.php
     *     - ROOT_PATH/App/Server/Polyfill/{$name}.php
     *
     * If a polyfill file is found, it is required, and the function name is returned as a string.
     * If a polyfill file is not found, an \Error is thrown with the error message.
     * If the function already exists, the function name is returned as a string.
     *
     * @param string $name The name of the function
     * @return string|null  The name of the function if a polyfill is required and found, otherwise null
     *
     * @throws Error       If a polyfill is required but not found, or there is an error while requiring the file
     */
    public static function _function(string $name) : string|null
    {
        if (\function_exists($name) === false) {
            $path = (FLAMES_PATH . 'Kernel/Polyfill/' . $name . '.php');

            $error = null;
            try {
                @require $path;
            } catch (\Error $e) {
                $error = $e->getMessage();

                try {
                    @require (APP_PATH . 'Server/Polyfill/' . $name . '.php');
                    $error = null;
                } catch (\Error $e) {
                    $error = $e->getMessage();
                }
            }

            if ($error !== null) {
                throw new Error($error);
            }

            return $name;
        }

        return $name;
    }

    /**
     * file
     *
     * Requires a file at the given path.
     *
     * @param string $path The path to the file
     * @param bool $includeRootFullPath Whether to prepend ROOT_PATH to the path
     *
     * @return void
     */
    public static function file(string $path, $includeRootFullPath = false)
    {
        if ($includeRootFullPath === true) {
            $path = (ROOT_PATH . $path);
        }

        require $path;
    }
}