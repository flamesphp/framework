<?php

namespace Flames\AutoLoad
{
    use Flames\Kernel\Client\Virtual;

    /**
     * Class AutoLoad
     *
     * This class provides autoloading functionality for PHP classes.
     * It registers the autoloader function and loads the required class files
     * based on the namespace or file path.
     *
     * @internal
     */
    final class Client
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
//        $path = (APP_PATH . 'Server/Event/Load.php');
//        if (file_exists($path) === true) {
//            self::$event = true;
//        }

            \spl_autoload_register(function ($name) {
                self::onLoad($name);
            });
        }

        /**
         * Handles the auto-loading of classes.
         *
         * This method is responsible for loading classes automatically based on their namespace.
         * It follows different loading mechanisms for classes in the "Flames" namespace and the "App" namespace.
         *
         * @param string $name The name of the class being loaded.
         * @return void
         */
        protected static function onLoad(string $name): void
        {
            if (Virtual::load($name) === false && self::$event === true) {
//                Event::dispatch('Load', 'onLoad', $name);
            }
        }
    }
}