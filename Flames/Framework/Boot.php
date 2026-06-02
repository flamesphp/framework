<?php

namespace Flames\Framework;

use Flames\Env\Env;
use Flames\Forge\Cli;
use Flames\Errors;
use Flames\Required;

/**
 * @internal
 */
class Boot
{
    public static ?Errors\Run $errorHandler = null;

    public static function run()
    {
        self::registerErrorHandler();
        self::registerDumpper();
        Dispatch::run();
    }

    protected static function registerErrorHandler()
    {
        if (Env::get('ERROR_HANDLER_ENABLED') === true) {
            self::$errorHandler = new Errors\Run;
            $pageHandler = new Errors\Handler\PrettyPageHandler();
            $pageHandler->setEditor('phpstorm');
            self::$errorHandler->pushHandler($pageHandler);
            self::$errorHandler->pushHandler(new Errors\Handler\PlainTextHandler());
            self::$errorHandler->register();
        }
    }

    protected static function registerDumpper()
    {
        require (FLAMES_PATH . 'autoload/resources/functions.php');
    }
}