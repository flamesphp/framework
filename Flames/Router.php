<?php

namespace Flames;

use Flames\Collection\Arr;
use Flames\Collection\Strings;

/**
 * Class Router
 *
 * The Router class handles routing and matching of routes.
 */
class Router
{
    protected Arr|null $routes = null;

    /**
     * Constructor for the class.
     *
     * @return void
     */
    public function __construct()
    {
        $this->routes = Arr();
    }

    /**
     * Adds a new route to the list of routes.
     *
     * @param mixed $method The HTTP method for the route, default is 'GET'.
     * @param mixed $route The URL route, default is '/'.
     * @param mixed $controller The controller for the route.
     * @param mixed $controllerMethod The controller method for the route, default is 'onRequest'.
     *
     * @return void
     */
    public function add(mixed $method = 'GET', mixed $route = '/', mixed $controller = null, mixed $controllerMethod = 'onRequest')
    {
        $route          = (string)$route;
        $method         = (string)$method;
        $controller     = (string)$controller;
        $delegateString = (string)$controllerMethod;

        $routeData = Arr();

        while (Strings::contains($route, ' ') === true) {
            $route = Strings::remove($route, ' ');
        }

        $routeData->controller = $controller;
        if (Strings::isEmpty($routeData->controller) === true) {
            return null;
        }

        $routeData->routeFormatted = $route;
        $routeData->parameters     = Arr();
        $routeData->methods        = $method;
        $routeData->delegate       = $delegateString;

        $routeParsed = ('' . $route);

        while (Strings::indexOf($routeParsed,'{') !== false) {
            $initIndexOf  = Strings::indexOf($routeParsed,'{');
            $extract      = Strings::sub($routeParsed, $initIndexOf + 1);
            $closeIndexOf = Strings::indexOf($extract, '}');

            if ($closeIndexOf === null) {
                break;
            }
            $extract = Strings::sub($extract, 0, $closeIndexOf);
            if (Strings::contains($extract, '{') === true || Strings::contains($extract,'}') === true) {
                break;
            }

            $routeParsed = (Strings::sub($routeParsed, 0, $initIndexOf) .
                Strings::sub($routeParsed, $initIndexOf + $closeIndexOf + 2));
            $routeData->parameters->add($extract);
        }

        // Case insensitive
        if ($routeData->parameters->count > 0) {
            $routeCaseInsensitive = ('' . $routeData->routeFormatted);
            for ($i = 0; $i < $routeData->parameters->count; $i++) {
                $routeCaseInsensitive = Strings::replace($routeCaseInsensitive, '{' . $routeData->parameters[$i] . '}', '{%parameter' . $i . '%}');
            }

            $routeCaseInsensitive = Strings::toLower($routeCaseInsensitive);
            for ($i = 0; $i < $routeData->parameters->count; $i++) {
                $routeCaseInsensitive = Strings::replace($routeCaseInsensitive, '{%parameter' . $i . '%}', '{' . $routeData->parameters[$i] . '}');
            }
        } else  {
            $routeData->routeFormatted = Strings::toLower($routeData->routeFormatted);
        }

        $this->routes->add($routeData);
    }

    /**
     * Retrieves the match based on the current environment.
     *
     * @return Arr|null The match based on the current environment.
     */
    public function getMatch() : Arr|null
    {
        if (\Flames\Forge\Cli::isCli() === false) {
            return self::getMatchWeb();
        }

        return $this->getMatchCLI();
    }

    /**
     * Retrieves the matched web route information.
     *
     * @return Arr|null The matched web route information, or null if no match is found.
     */
    protected function getMatchWeb() : Arr|null
    {
        // Mount router
        $router = new Router\Parser();

        $paramItems = Arr();
        for ($i = 0; $i < $this->routes->count; $i++) {
            $route = ($this->routes[$i]);
            if ($route->methods === 'CLI') {
                continue;
            }
            $routeParsed = $route->routeFormatted;

            foreach ($route->parameters as $param) {
                $paramItems->add($param);
                $routeParsed = Strings::replace($routeParsed,
                    '{' . $param . '}',
                    '[*:item' . $paramItems->count . 'item]');
            }

            $router->map($route->methods, $routeParsed, null, $i);
        }

        // Try get
        $match = $router->match();

        if ($match === false || $match === null) {
            return null;
        }

        $route = ($this->routes[($match['name'])]);
        $parameters = Arr();
        $matchParameters = Arr($match['params']);

        foreach ($route->parameters as $param) {
            $encodedItem = null;
            for ($i = 0; $i < $paramItems->count; $i++)
                if ($paramItems[$i] == $param) {
                    $break = false;
                    $encodedItem = ('item' . ($i + 1) . 'item');

                    foreach ($matchParameters as $_paramEnc => $value)
                        if ($encodedItem == $_paramEnc) {
                            $parameters[$param] = $value;
                            $break = true;
                            break;
                        }

                    if ($break == true)
                        break;
                }
        }

        // Parse parameters values to case sensitive
        $currentUrl = $_SERVER['REQUEST_URI'];
        $currentUrlLower = Strings::toLower($currentUrl);
        $caseSensitiveParameters = Arr();

        foreach ($parameters as $key => $parameter) {
            $valid = false;
            $indexOfInit = Strings::indexOf($currentUrlLower, $parameter);
            if ($indexOfInit !== null) {
                $extractInit = Strings::sub($currentUrl, $indexOfInit);
                $extractFinish = Strings::sub($extractInit, 0, -1);
                if (Strings::toLower($extractFinish) == $parameter) {
                    $caseSensitiveParameters[$key] = $extractFinish;
                    $valid = true;
                }
            }
            if ($valid === false) {
                $caseSensitiveParameters[$key] = $parameter;
            }
        }

        return Arr([
            'url'        => $currentUrl,
            'command'    => null,
            'controller' => $route->controller,
            'parameters' => $caseSensitiveParameters,
            'delegate'   => $route->delegate
        ]);
    }

    /**
     * Retrieves the matched CLI route.
     *
     * @return Arr|null Returns the matched route as an Arr object if found, otherwise returns null.
     */
    protected function getMatchCLI() : Arr|null
    {
        $args = $_SERVER['argv'];
        if (count($args) === 1) {
            return null;
        }

        $command = $args[1];

        // Match
        foreach ($this->routes as $route) {
            if ($route->methods !== 'CLI') {
                continue;
            }

            if ($route->routeFormatted === $command) {
                return Arr([
                    'url'        => null,
                    'command'    => $command,
                    'controller' => $route->controller,
                    'parameters' => $route->parameters,
                    'delegate'   => $route->delegate
                ]);
            }
        }

        return null;
    }

    /**
     * Retrieves the metadata for the routes.
     *
     * @return Arr The metadata array.
     */
    public function getMetadata() : Arr
    {
        return $this->routes;
    }
}