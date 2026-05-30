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
            self::onLoad((string)$name);
        });
    }

    /**
     * Handles the auto-loading of classes.
     *
     * This method is responsible for loading classes automatically based on their namespace.
     * It follows different loading mechanisms for classes in the "Flames", "App" and "Microservice" namespaces.
     *
     * Namespace → path mapping:
     *   Flames\Collection\*          → COLLECTION_PATH/Collection/{rest}.php  (flamesphp/collection package)
     *   Flames\Dumpper\*             → DUMPPER_PATH/Dumpper/{rest}.php          (flamesphp/dumpper package)
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
        // Case Flames\Collection — loaded from the standalone flamesphp/collection package
        if (str_starts_with($name, 'Flames\\Collection\\')) {
            $relative = substr(str_replace('\\', '/', $name), 6) . '.php'; // 'Collection/Arr.php'
            $path     = COLLECTION_PATH . $relative;
            require $path;
            return;
        }

        /* Case Flames\Dumpper — loaded from the standalone flamesphp/dumpper package (PSR-4: Flames/Dumpper/) */
        if (str_starts_with($name, 'Flames\\Dumpper\\')) {
            $relative = substr(str_replace('\\', '/', $name), 14) . '.php'; /* strips 'Flames/Dumpper' → e.g. '/Inc/DumpHelper.php' */
            $path     = DUMPPER_PATH . 'Dumpper' . $relative;
            require $path;
            return;
        }

        /* Case Flames\Library — loaded from the standalone flamesphp/composer package (scoped composer) */
        if (str_starts_with($name, 'Flames\\Library\\')) {
            static $libraryBoot = false;
            if ($libraryBoot === false) {
                $libraryBoot = true;
                require LIBRARY_PATH . 'Library/AutoLoad.php';
                \Flames\Library\AutoLoad::register();
            }
            \Flames\Library\AutoLoad::load($name);
            return;
        }

        /* Case Flames\Docker — loaded from the standalone flamesphp/docker package (PSR-4: Flames/Docker/) */
        if (str_starts_with($name, 'Flames\\Docker\\')) {
            $relative = substr(str_replace('\\', '/', $name), 6) . '.php'; /* strips 'Flames' → e.g. '/Docker/Docker.php' */
            $path     = DOCKER_PATH . $relative;
            require $path;
            return;
        }

        /* Case Flames\Forge — loaded from the standalone flamesphp/forge package (PSR-4: Flames/Forge/) */
        if (str_starts_with($name, 'Flames\\Forge\\')) {
            $relative = substr(str_replace('\\', '/', $name), 6) . '.php'; /* strips 'Flames' → e.g. '/Forge/Cli.php' */
            $path     = FORGE_PATH . $relative;
            require $path;
            return;
        }

        /* Case Flames\Date\* — loaded from the standalone flamesphp/date package (PSR-4: Flames/Date/) */
        if (str_starts_with($name, 'Flames\\Date\\')) {
            $relative = substr(str_replace('\\', '/', $name), 6) . '.php'; // strips 'Flames' → '/Date/DateTime.php'
            $path     = DATE_PATH . 'Flames' . $relative;                  // DATE_PATH/Flames/Date/DateTime.php
            if (file_exists($path)) {
                require $path;
            }
            return;
        }

        /* Case Carbon\* — legacy Carbon namespace, bridged into Flames\Date\* via alias.
         * This keeps third-party code that uses Carbon\Carbon etc. working. */
        if (str_starts_with($name, 'Carbon\\')) {
            $flamesName = 'Flames\\Date\\' . substr($name, strlen('Carbon\\'));
            if (!class_exists($flamesName, false) && !interface_exists($flamesName, false)) {
                spl_autoload_call($flamesName);
            }
            if ((class_exists($flamesName, false) || interface_exists($flamesName, false))
                && !class_exists($name, false) && !interface_exists($name, false)
            ) {
                class_alias($flamesName, $name);
            }
            return;
        }

        /* Case Flames\Mesh and Flames\Mesh\* — loaded from flamesphp/mesh package */
        if ($name === 'Flames\\Mesh' || str_starts_with($name, 'Flames\\Mesh\\')) {
            $path = MESH_PATH . str_replace('\\', '/', $name) . '.php';
            require $path;
            return;
        }

        /* Case Flames\Orm\* + Flames\Model / Repository / Database — flamesphp/orm package */
        if (str_starts_with($name, 'Flames\\Orm\\')
            || $name === 'Flames\\Model'
            || $name === 'Flames\\Repository'
            || $name === 'Flames\\Database'
        ) {
            $path = ORM_PATH . str_replace('\\', '/', $name) . '.php';
            require $path;
            return;
        }

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
