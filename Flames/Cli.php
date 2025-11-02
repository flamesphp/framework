<?php

namespace Flames;

/**
 * Represents a utility class for detecting CLI (Command-Line Interface) environment in PHP.
 */
final class Cli
{
    /**
     * Checks if the current script is being executed in a Command Line Interface (CLI) environment.
     *
     * @return bool Returns true if the script is being executed in CLI, otherwise false.
     */
    public static function isCli() : bool
    {
        return (
            Kernel::MODULE === 'SERVER' && (
                $_SERVER['SCRIPT_FILENAME'] === 'bin' ||
                $_SERVER['SCRIPT_FILENAME'] === 'vendor/flamesphp/framework/bin'
            )
          );
    }
}