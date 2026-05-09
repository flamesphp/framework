<?php

namespace Flames;

/**
 * Class AutoLoad
 *
 * This class provides autoloading functionality for PHP classes.
 * It registers the autoloader function and loads the required class files
 * based on the namespace or file path.
 *
 * @internal
 */
final class AutoLoad
{
    public static bool $event = false;

    /**
     * Runs the application.
     *
     * This method is responsible for initializing the application and starting its execution.
     * It verifies if the event load file exists and registers the event if it does.
     *
     * @return void
     */
    public static function run(): void
    {
        // Verify if event load exists and register
        $path = (APP_PATH . 'Server/Event/Load.php');
        if (file_exists($path) === true) {
            self::$event = true;
        }

        \spl_autoload_register(function ($name) {
            self::onLoad($name);
        });
    }

    /**
     * Handles the auto-loading of classes.
     *
     * This method is responsible for loading classes automatically based on their namespace.
     * It follows different loading mechanisms for classes in the "Flames", "App" and "Microservice" namespaces.
     *
     * Namespace → path mapping:
     *   Flames\*                     → FLAMES_PATH/{rest}.php
     *   App\*                        → APP_PATH/{rest}.php
     *   Microservice\Test\Server\*   → ROOT_PATH/Microservice/Test/Server/{rest}.php
     *   Microservice\Test\Client\*   → ROOT_PATH/Microservice/Test/Client/{rest}.php
     *
     * @param string $name The name of the class being loaded.
     * @return void
     */
    protected static function onLoad(string $name): void
    {
        // Case Flames Internal
        if (str_starts_with($name, 'Flames\\')) {
            $name = substr(str_replace('\\', '/', $name), 7);
            $path = (FLAMES_PATH . $name . '.php');
            require $path;
        }

        // Case App (standard App\Server\* and App\Client\*)
        elseif (str_starts_with($name, 'App\\')) {
            $path = (APP_PATH . substr(str_replace('\\', '/', $name), 4) . '.php');
            require $path;

            if (method_exists($name, '__constructStatic') === true) {
                // Only skip __constructStatic for App\Client\* (parts[1] === 'Client').
                // Server-side classes that happen to contain 'Client' elsewhere in their
                // namespace (e.g. App\Server\Model\Client) must still be initialised.
                $parts = explode('\\', $name);
                if (($parts[1] ?? null) !== 'Client') {
                    ($name . '::__constructStatic')();
                }
            }
        }

        // Case Microservice (Microservice\{Name}\Server\* and Microservice\{Name}\Client\*)
        // The namespace maps directly to ROOT_PATH:
        //   Microservice\Test\Server\Controller\Index
        //   → ROOT_PATH . 'Microservice/Test/Server/Controller/Index.php'
        elseif (str_starts_with($name, 'Microservice\\')) {
            $path = (ROOT_PATH . str_replace('\\', '/', $name) . '.php');
            require $path;

            if (method_exists($name, '__constructStatic') === true) {
                // Skip __constructStatic for Microservice\{Name}\Client\* (parts[2] === 'Client').
                $parts = explode('\\', $name);
                if (($parts[2] ?? null) !== 'Client') {
                    ($name . '::__constructStatic')();
                }
            }
        }

        elseif (self::$event === true) {
            Event::dispatch('Load', 'onLoad', $name);
        }
    }
}
