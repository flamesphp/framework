<?php
declare(strict_types=1);


namespace Flames;

use Flames\Collection\Arr;
use Flames\Framework\Controller\Response;
use Flames\Env\Env;
use Flames\Framework\Boot;
use Flames\Framework\Cache;
use Flames\Framework\Event;
use Flames\Router;
use Flames\Interfaces\Event\Initialize as InitializeContract;
use Flames\Interfaces\Event\Route as RouteContract;
use Flames\Framework\Connection;
use Flames\Framework\Header;
use Flames\Framework\Controller\RequestMount;
use Flames\Reflection\Reflection;
use Flames\Reflection\ReflectionClass;
use Flames\Async\Async\Service;
use Flames\Autoload\Autoload;
use Flames\Microservice\Microservice;
/**
 * Class Kernel
 *
 * The Kernel class is responsible for handling the main execution flow of the application.
 *
 * @internal
 */
final class Kernel
{
    public const VERSION = '1.0.0';

    public static Errors\Run|null $errorHandler = null;


    public static function boot(bool $run = false): void
    {
        if (!self::setup()) {
            return;
        }

        Boot::register($run);
    }

    public static function setup(): bool
    {
        self::hookPaths();
        if (!self::autoload()) {
            return false;
        }

        Env::reload();
        return true;
    }

    protected static function hookPaths(): void
    {
        define('START_TIME', microtime(true));
        define('ROOT_PATH', (realpath(__DIR__ . '/../../../../') . '/'));
        define('FLAMES_PATH', ROOT_PATH . 'vendor/flamesphp/');
        define('APP_PATH', ROOT_PATH . 'App/');
        define('MODULE', 'SERVER');
    }

    protected static function autoload(): bool
    {
        try {
            require(FLAMES_PATH . 'autoload/Flames/Autoload/Autoload.php');
            Autoload::run();
            return true;
        } catch (\Throwable) {}

        return false;
    }
}