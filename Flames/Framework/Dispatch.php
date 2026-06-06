<?php
declare(strict_types=1);


namespace Flames\Framework;

use Flames\Framework\Connection;
use Flames\Framework\Controller\Response;
use Flames\Framework\Event;
use Flames\Framework\Header;
use Flames\Interfaces\Event\Initialize as InitializeContract;
use Flames\Interfaces\Event\Route as RouteContract;
use Flames\Framework\Controller\RequestMount;
use Flames\Router;

/**
 * @internal
 */
class Dispatch
{
    public static function run(): bool
    {
        return self::dispatch();
    }

    public static function dispatch(): bool
    {
        if (Event::dispatch(InitializeContract::class, 'Initialize', 'onInitialize') === false) {
            return false;
        }

        Event::dispatch(RouteContract::class, 'Route', 'onRoute');

        if (Router::hasRoutes() === false) {
            return false;
        }

        $match = Router::getMatch();
        if ($match === null) {
            return false;
        }

        return self::dispatchRoute($match);
    }

    protected static function dispatchRoute(\Flames\Router\RouteMatch $match): bool
    {
        $requestData = RequestMount::mountRequestData($match, Connection::getIp());
        $requestDataAllow = Event::dispatch(RouteContract::class, 'Route', 'onMatch', $requestData);
        if ($requestDataAllow === false) {
            return false;
        }

        $controller = new $match->controller();
        $response = Response::from($controller->onRequest($requestData));
        $output = $response->output;

        $_output = Event::dispatch('Output', 'onOutput', $requestData, $output);
        if ($_output !== null) {
            $output = (string) $_output;
        }

        Header::set('Code', $response->code);
        Header::set('Content-Type', $response->contentType);
        Header::send();

        if (str_starts_with($output, '{"flames.redirect":') === true) {
            $decode = json_decode($output);
            header('Location: ' . $decode->{"flames.redirect"});
            exit;
        }

        echo $output;

        return true;
    }
}
