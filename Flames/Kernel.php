<?php

namespace Flames;

use Flames\Collection\Arr;
use Flames\Controller\Response;
use Flames\Kernel\Route;
use Flames\Router\Client;

/**
 * Class Kernel
 *
 * The Kernel class is responsible for handling the main execution flow of the application.
 *
 * @internal
 */
final class Kernel
{
    public const VERSION = 'v1.0.0';
    public const MODULE  = 'SERVER';
    public const CDN_VERSION = 'v1.0.0';

    protected static Router|null $defaultRouter = null;
    protected static ErrorHandler\Run|null $errorHandler = null;

    /**
     * Runs the application.
     *
     * This method sets up the application, dispatches events, and handles the execution flow of the application.
     *
     * @return void
     */
    public static function run() : void
    {
        if (self::setup() === false) {
            return;
        }

        $dispatchCLI = false;
        $isCLI = Cli::isCli();
        if (self::dispatchEvents() === false) {
            if ($isCLI === true) {
                self::dispatchCLI();
                $dispatchCLI = true;
            } else {
                if (self::renderDirectFile() === true) {
                    return;
                }
//                if (self::renderFavIcon() === true) {
//                    return;
//                }
                ErrorPage::dispatch404();
                self::shutdown();
                return;
            }
        }

        if ($dispatchCLI === false && $isCLI === true) {
            self::dispatchCLI();
        }

        self::shutdown();
    }

    /**
     * Sets up the application by performing various initialization tasks.
     *
     * @return void
     */
    protected static function setup() : bool
    {
        ob_start();
        define('START_TIME', microtime(true));
        define('ROOT_PATH', self::getRootPath());
        define('APP_PATH', ROOT_PATH . 'App/');

        require(FLAMES_PATH . 'AutoLoad.php');
        AutoLoad::run();

        try {
            mb_internal_encoding('UTF-8');
        } catch (\Error $e) {
            Required::file(FLAMES_PATH . 'Kernel/Missing/mbstring.php');
            return false;
        }

        Required::file(FLAMES_PATH . 'Kernel/Wrapper/Raw.php');

        self::setEnvironment();
        self::loadPolyfill();

        if (Cli::isCli() === false) {
            self::setErrorHandler();
        }
        self::setDumpper();
        self::setDate();

        return true;
    }

    /**
     * Sets the environment for the application by injecting the environment variables.
     *
     * @return void
     */
    protected static function setEnvironment() : void
    {
        $environment = new Environment();
        $environment->inject();
    }

    /**
     * Loads polyfill functions based on the configuration.
     *
     * @return void
     */
    protected static function loadPolyfill() : void
    {
        $polyfill = Environment::get('POLYFILL_FUNCTIONS');
        if ($polyfill !== null) {
            $polyfills = explode(',', Environment::get('POLYFILL_FUNCTIONS'));
            foreach ($polyfills as $_polyfill) {
                Required::_function($_polyfill);
            }
        }

        Required::_function('parse_raw_http_request');
    }

    /**
     * Sets up the error handler for the application.
     *
     * @return void
     */
    protected static function setErrorHandler() : void
    {
        if (Environment::get('ERROR_HANDLER_ENABLED') === true) {
            self::$errorHandler = new ErrorHandler\Run;
            $pageHandler = new ErrorHandler\Handler\PrettyPageHandler();
            $pageHandler->setEditor(Environment::get('ERROR_HANDLER_IDE'));
            self::$errorHandler->pushHandler($pageHandler);
            self::$errorHandler->register();
        }
    }

    /**
     * Returns the error handler instance.
     *
     * @return ErrorHandler\Run The error handler instance.
     */
    public static function getErrorHandler() : ErrorHandler\Run
    {
        return self::$errorHandler;
    }

    /**
     * Sets up the Dumpper for the application.
     *
     * This method configures the Dumpper based on the application's environment.
     * If dump is enabled, it sets the Dumpper theme and editor, and registers the Dumpper.
     * If dump is disabled, it uses the Plain Dumpper.
     *
     * @return void
     */
    protected static function setDumpper() : void
    {
        if (Environment::get('DUMP_ENABLED') === true) {
            Dump\Dump::$theme = Dump\Dump::THEME_SOLARIZED_DARK;
            Dump\Dump::$editor = Environment::get('DUMP_IDE');
            Required::file(FLAMES_PATH . 'Dump/Register.php');
        }
        else {
            Required::file(FLAMES_PATH . 'Dump/Plain.php');
        }
    }

    protected static function setDate(): void
    {
        $timezone = Environment::get('DATE_TIMEZONE');
        if ($timezone !== null && $timezone !== '') {
            \date_default_timezone_set($timezone);
            return;
        }
        \date_default_timezone_set('UTC');
    }

    /**
     * Dispatches events for the application.
     *
     * @return bool Returns true if the events were successfully dispatched, false otherwise.
     */
    protected static function dispatchEvents() : bool
    {
        Header::set('X-Powered-By', 'Flames');

        if (Event::dispatch('Initialize', 'onInitialize') === false) {
            return false;
        }

        if (Cli::isCli() === false && str_starts_with(@$_SERVER['REQUEST_URI'], '/flames')) {
            if (Client::run(@$_SERVER['REQUEST_URI']) !== false) {
                return true;
            }
        }

        self::$defaultRouter = Event::dispatch('Route', 'onRoute', new Router());
        if (self::$defaultRouter !== null) {
            $match = self::$defaultRouter->getMatch();
            if ($match === null) {
                return false;
            }

            return self::dispatchRoute($match);
        }

        return false;
    }

    /**
     * Dispatches the route and executes the corresponding controller action.
     *
     * @param object $routeData The data of the matched route.
     * @return bool Whether the route dispatching was successful or not.
     */
    protected static function dispatchRoute($routeData) : bool
    {
        $requestData = Route::mountRequestData($routeData, Connection::getIp());
        $requestDataAllow = Event::dispatch('Route', 'onMatch', $requestData);
        if ($requestDataAllow === false) {
            return false;
        }

        $controller = new $routeData->controller();
        $response = $controller->{$routeData->delegate}($requestData);

        if (($response instanceof Response) === false) {
            $response = new Response($response);
        }

        $output = $response->output;

        $_output = Event::dispatch('Output', 'onOutput', $requestData, $output);
        if ($_output !== null) {
            $output = (string)$_output;
        }

        self::sendHeaders($response->headers, $response->code);
        if (str_starts_with($output, '{"flames.redirect":') === true) {
            if (Cli::isCli() === false) {
                $decode = json_decode($output);
                header('Location: ' . $decode->{"flames.redirect"});
                exit;
            } else { // if autobuild
                // TODO: transform page in html redirect on static build
            }
        }
        echo $output;

        return true;
    }

    /**
     * Dispatches the command line interface (CLI)
     *
     * @return bool|null
     */
    protected static function dispatchCLI() : bool|null
    {
        $system = new Cli\System();
        return $system->run();
    }

    /**
     * Renders the requested file directly to the output buffer.
     *
     * @return bool Indicates whether the file was successfully rendered or not.
     */
    protected static function renderDirectFile() : bool
    {
        $uri = explode('?', @$_SERVER['REQUEST_URI'])[0];

        if (str_starts_with($uri, '/') === true) {
            $uri = substr($uri, 1);
        }
        if (str_contains($uri, '\\') === true) {
            $uri = str_replace('\\', '/', $uri);
        }
        while (str_contains($uri, '../') === true) {
            $uri = str_replace('../', '', $uri);
        }
        while (str_contains($uri, '//') === true) {
            $uri = str_replace('//', '/', $uri);
        }

        $path = (APP_PATH . 'Client/Public/' . $uri);

        if (file_exists($path) === false || is_dir($path) === true) {
            return false;
        }
        
        header('Content-Type: ' . mime_content_type($path));

        $fileStream = fopen($path, 'r');
        while(!feof($fileStream)) {
            $buffer = fgets($fileStream, 128000); // 128 kb
            echo $buffer;
        }
        fclose($fileStream);

        return true;
    }

    /**
     * Sets the HTTP headers for the response.
     *
     * @param Arr|null $headers An associative array of header names and values. If null, no additional headers will be set.
     * @param int $code The HTTP status code to set.
     * @return void
     */
    protected static function sendHeaders(Arr|null $headers, int $code) : void
    {
        Header::set('Code', $code);

        if ($headers !== null) {
            foreach ($headers as $key => $value) {
                Header::set($key, $value);
            }
        }

        Header::send();
    }

    /**
     * Handles the shutdown of the application.
     *
     * @return void
     */
    public static function shutdown() : void
    {
        if (Cli::isCli() === true && \Flames\Cli\Command\Coroutine::isCoroutineRunning() === true) {
            \Flames\Cli\Command\Coroutine::errorHandler();
        }

//        $runTime = microtime(true) - constant('START_TIME');
//        dump($runTime);
    }

    /**
     * Returns the default router instance.
     *
     * @return Router|null The default router instance, or null if it has not been set.
     */
    public static function getDefaultRouter() : Router|null
    {
        return self::$defaultRouter;
    }

    protected static function getRootPath()
    {
        $path = (realpath(__DIR__ . '/../') . '/');
        define('FLAMES_PATH', $path . 'Flames/');

        if (str_ends_with(str_replace('\\', '/', $path), 'vendor/flamesphp/framework/') === true) {
            define('FLAMES_COMPOSER', true);
            return (realpath($path . '../../../') . '/');
        } else {
            define('FLAMES_COMPOSER', false);
            return $path;
        }
    }
}