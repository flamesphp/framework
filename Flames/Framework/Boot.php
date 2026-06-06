<?php
declare(strict_types=1);


namespace Flames\Framework;

use Flames\Env\Env;
use Flames\Forge\Cli;
use Flames\Errors;
use Flames\Interfaces\Event\Route as RouteContract;
use Flames\Required;

/**
 * @internal
 */
class Boot
{
    /**
     * @var Errors\Run|null
     */
    public static ?Errors\Run $errorHandler = null;

    public static function register(bool $run = false)
    {
        self::registerDate();
        self::registerDumpper();
        self::registerErrorHandler();
        self::registerRoute();

        if ($run === true) {
            self::run();
        }
    }

    public static function run(): void
    {
        try {
            Dispatch::run();
        } catch (\Throwable $e) {
            self::renderException($e);
        }
    }

    public static function renderException(\Throwable $e, ?\Throwable $handlerError = null): void
    {
        try {
            if (self::$errorHandler !== null) {
                self::$errorHandler->allowQuit(false);
                self::$errorHandler->writeToOutput(false);
                $output = (string) self::$errorHandler->handleException($e);

                if ($output !== '') {
                    self::sendErrorResponse(self::$errorHandler->sendHttpCode() ?: 500, 'text/html; charset=UTF-8');
                    echo $output;
                    return;
                }
            }

            self::fallbackException($e, $handlerError);
        } catch (\Throwable $inner) {
            self::fallbackException($e, $inner);
        }
    }

    public static function fallbackException(\Throwable $e, ?\Throwable $handlerError = null): void
    {
        self::sendErrorResponse(500, 'text/plain; charset=UTF-8');
        echo get_class($e) . ': ' . $e->getMessage() . "\n";
        echo $e->getFile() . ':' . $e->getLine() . "\n\n";
        echo $e->getTraceAsString();

        if ($handlerError !== null) {
            echo "\n\n--- Error handler failed ---\n";
            echo get_class($handlerError) . ': ' . $handlerError->getMessage() . "\n";
            echo $handlerError->getFile() . ':' . $handlerError->getLine();
        }
    }

    protected static function sendErrorResponse(int $code, string $contentType): void
    {
        if (!Errors\Util\Misc::canSendHeaders()) {
            return;
        }

        @http_response_code($code);
        header('Content-Type: ' . $contentType);
    }

    public static function registerWebHandlers(): void
    {
        self::registerDumpper();
        self::registerErrorHandler();
    }

    protected static function registerErrorHandler(): void
    {
        if (Env::get('ERROR_HANDLER_ENABLED') !== true) {
            return;
        }

        self::$errorHandler = new Errors\Run;
        $pageHandler = new Errors\Handler\PrettyPageHandler();
        $pageHandler->setEditor('phpstorm');

        if (self::usesPrettyPageHandler()) {
            $pageHandler->handleUnconditionally(true);
            self::$errorHandler->pushHandler($pageHandler);
        } else {
            self::$errorHandler->pushHandler($pageHandler);
            self::$errorHandler->pushHandler(new Errors\Handler\PlainTextHandler());
        }

        self::$errorHandler->register();
    }

    protected static function usesPrettyPageHandler(): bool
    {
        return defined('FLAMES_READY_WORKER') || !Cli::isCli();
    }

    protected static function registerDumpper(): void
    {
        if (!function_exists('once')) {
            require(FLAMES_PATH . 'autoload/resources/functions.php');
        }
    }

    protected static function registerDate(): void
    {
        $timezone = Env::get('DATE_TIMEZONE');
        if ($timezone !== null && $timezone !== '') {
            @\date_default_timezone_set($timezone);
            return;
        }
        \date_default_timezone_set('UTC');
    }

    protected static function registerRoute()
    {
        Event::dispatch(RouteContract::class, 'Route', 'onRoute');
    }
}