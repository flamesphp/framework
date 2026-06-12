<?php
declare(strict_types=1);


namespace Flames\Framework;

use Flames\Collection\Arr;
use Flames\Framework\Connection;
use Flames\Framework\Controller\Data as ControllerData;
use Flames\Framework\Controller\Response;
use Flames\Framework\Event;
use Flames\Framework\Header;
use Flames\Framework\View as FrameworkView;
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

        $controllerClass = $match->controller;
        $controller = new $controllerClass();
        $result = $controller->onRequest($requestData);

        $viewPath = ControllerData::getViewPath($controllerClass, 'onRequest');
        $response = self::buildResponse($result, $viewPath);

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

    protected static function buildResponse(mixed $result, ?string $viewPath): Response
    {
        if ($viewPath === null) {
            return Response::from($result);
        }

        $viewData = null;
        $statusCode = 200;

        if ($result instanceof Response) {
            $viewData = $result->getViewData();
            if ($viewData === null) {
                return $result;
            }

            $statusCode = $result->statusCode;
        } elseif (is_array($result) || $result instanceof Arr) {
            $viewData = $result;
        } else {
            return Response::from($result);
        }

        $view = new FrameworkView();
        $view->addView($viewPath);

        return new Response(
            $view->render($viewData),
            $statusCode,
            'text/html',
        );
    }
}
