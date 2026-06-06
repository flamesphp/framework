<?php
declare(strict_types=1);


namespace Flames\Framework;

use Flames\Collection\Arr;
use Flames\Collection\Strings;

/**
 * Class Router
 *
 * The Router class handles routing and matching of routes.
 */
final class Router
{
    protected static Arr|null $routes = null;

    private function __construct()
    {
    }

    protected static function routes(): Arr
    {
        if (self::$routes === null) {
            self::$routes = Arr();
        }

        return self::$routes;
    }

    public static function clear(): void
    {
        self::$routes = Arr();
    }

    public static function hasRoutes(): bool
    {
        return self::routes()->count > 0;
    }

    /**
     * Adds a new route to the list of routes.
     *
     * @param mixed $method The HTTP method for the route, default is 'GET'.
     * @param mixed $route The URL route, default is '/'.
     * @param mixed $controller The controller for the route.
     *
     * @return void
     */
    public static function add(mixed $method = 'GET', mixed $route = '/', mixed $controller = null): void
    {
        $route      = (string)$route;
        $method     = (string)$method;
        $controller = (string)$controller;

        $routeData = Arr();

        while (Strings::contains($route, ' ') === true) {
            $route = Strings::remove($route, ' ');
        }

        $routeData->controller = $controller;
        if (Strings::isEmpty($routeData->controller) === true) {
            return;
        }

        $routeData->routeFormatted = $route;
        $routeData->parameters     = Arr();
        $routeData->methods          = $method;

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
        } else {
            $routeData->routeFormatted = Strings::toLower($routeData->routeFormatted);
        }

        self::routes()->add($routeData);
    }

    /**
     * Retrieves the match based on the current environment.
     *
     * @return Arr|null The match based on the current environment.
     */
    public static function getMatch(): ?Arr
    {
        if (\Flames\Forge\Cli::isCli() === false) {
            return self::getMatchWeb();
        }

        return self::getMatchCLI();
    }

    /**
     * Retrieves the matched web route information.
     *
     * @return Arr|null The matched web route information, or null if no match is found.
     */
    protected static function getMatchWeb(): ?Arr
    {
        $router = new Router\Parser();

        $paramItems = Arr();
        $routes     = self::routes();
        for ($i = 0; $i < $routes->count; $i++) {
            $route = ($routes[$i]);
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

            $router->map($route->methods, $routeParsed, null, (string) $i);
        }

        $match = $router->match();

        if ($match === false || $match === null) {
            return null;
        }

        $route = ($routes[(int) $match['name']]);
        $parameters = Arr();
        $matchParameters = Arr($match['params']);

        foreach ($route->parameters as $param) {
            $encodedItem = null;
            for ($i = 0; $i < $paramItems->count; $i++) {
                if ($paramItems[$i] == $param) {
                    $break = false;
                    $encodedItem = ('item' . ($i + 1) . 'item');

                    foreach ($matchParameters as $_paramEnc => $value) {
                        if ($encodedItem == $_paramEnc) {
                            $parameters[$param] = $value;
                            $break = true;
                            break;
                        }
                    }

                    if ($break == true) {
                        break;
                    }
                }
            }
        }

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
        ]);
    }

    /**
     * Retrieves the matched CLI route.
     *
     * @return Arr|null Returns the matched route as an Arr object if found, otherwise returns null.
     */
    protected static function getMatchCLI(): ?Arr
    {
        $args = $_SERVER['argv'];
        if (count($args) === 1) {
            return null;
        }

        $command = $args[1];

        foreach (self::routes() as $route) {
            if ($route->methods !== 'CLI') {
                continue;
            }

            if ($route->routeFormatted === $command) {
                return Arr([
                    'url'        => null,
                    'command'    => $command,
                    'controller' => $route->controller,
                    'parameters' => $route->parameters,
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
    public static function getMetadata(): Arr
    {
        return self::routes();
    }
}
