<?php

namespace Flames\Cli\Command;

use Flames\Cli\Output;
use Flames\Event;
use Flames\Router;

/**
 * @internal
 *
 * Lists server-side or client-side routes.
 *
 * Usage:
 *   forge route:server:list                   — all HTTP routes
 *   forge route:server:list {microservice}    — filter by microservice
 *   forge route:client:list                   — all client-side routes
 *   forge route:client:list {microservice}    — filter by microservice
 */
final class Route
{
    protected string $side;
    protected string|null $microservice = null;

    public function __construct($data)
    {
        $command    = $data->command ?? '';
        $this->side = str_contains($command, 'server') ? 'server' : 'client';

        $args = (array)($data->argument ?? []);
        if (!empty($args[0])) {
            $this->microservice = $args[0];
        }
    }

    public function run(bool $debug = false): bool
    {
        $router = $this->loadRouter();
        if ($router === null) {
            Output::error('Could not load routes.');
            return false;
        }

        $routes = $router->getMetadata();

        $serverMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

        $filtered = [];
        foreach ($routes as $route) {
            $method = strtoupper($route->methods ?? '');

            if ($this->side === 'server' && !in_array($method, $serverMethods, true)) {
                continue;
            }

            if ($this->microservice !== null) {
                $controller = $route->controller ?? '';
                if (!str_contains(strtolower($controller), strtolower($this->microservice))) {
                    continue;
                }
            }

            $filtered[] = $route;
        }

        if (empty($filtered)) {
            $label = $this->side === 'server' ? 'server-side' : 'client-side';
            $msg   = $this->microservice
                ? "No {$label} routes found for microservice '{$this->microservice}'."
                : "No {$label} routes found.";
            Output::warning($msg);
            return true;
        }

        $title = $this->side === 'server' ? 'SERVER ROUTES' : 'CLIENT ROUTES';
        if ($this->microservice !== null) {
            $title .= ' — ' . strtoupper($this->microservice);
        }

        echo "\n" . Output::YELLOW . Output::BOLD . "  {$title}" . Output::RESET . "\n\n";

        $methodW = 8;
        $pathW   = 40;

        echo '  '
            . Output::YELLOW . Output::BOLD . str_pad('METHOD', $methodW) . Output::RESET . '  '
            . Output::YELLOW . Output::BOLD . str_pad('PATH', $pathW)     . Output::RESET . '  '
            . Output::YELLOW . Output::BOLD . 'CONTROLLER'                . Output::RESET . "\n";

        echo '  '
            . Output::GRAY . str_repeat('─', $methodW) . Output::RESET . '  '
            . Output::GRAY . str_repeat('─', $pathW)   . Output::RESET . '  '
            . Output::GRAY . str_repeat('─', 50)        . Output::RESET . "\n";

        foreach ($filtered as $route) {
            $method     = strtoupper($route->methods     ?? '');
            $path       = $route->routeFormatted          ?? '';
            $controller = $route->controller              ?? '';

            $methodColor = match ($method) {
                'GET'          => Output::GREEN,
                'POST'         => Output::CYAN,
                'PUT', 'PATCH' => Output::YELLOW,
                'DELETE'       => Output::RED,
                default        => Output::WHITE,
            };

            echo '  '
                . $methodColor . str_pad($method, $methodW) . Output::RESET . '  '
                . Output::WHITE . str_pad($path, $pathW)    . Output::RESET . '  '
                . Output::GRAY  . $controller               . Output::RESET . "\n";
        }

        echo "\n";
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────

    protected function loadRouter(): ?Router
    {
        $router = new Router();

        try {
            if ($this->side === 'server') {
                $result = Event::dispatch('Route', 'onRoute', $router);
                if ($result instanceof Router) {
                    $router = $result;
                }
                $this->loadMicroserviceServerRoutes($router);
            } else {
                $this->loadClientEventFiles($router);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return $router;
    }

    protected function loadClientEventFiles(Router $router): void
    {
        // Default app client event
        $this->callClientEventFile(
            ROOT_PATH . 'App/Client/Event/Route.php',
            '\\App\\Client\\Event\\Route',
            $router
        );

        // Microservice client events: Microservice/*/Client/Event/Route.php
        $microDir = ROOT_PATH . 'Microservice/';
        if (!is_dir($microDir)) {
            return;
        }
        foreach (glob($microDir . '*/Client/Event/Route.php') ?: [] as $file) {
            // e.g. Microservice/Admin/Client/Event/Route.php
            // → namespace Microservice\Admin\Client\Event\Route
            $rel     = str_replace(ROOT_PATH, '', str_replace('\\', '/', $file));
            $class   = '\\' . str_replace('/', '\\', rtrim($rel, '.php'));
            $this->callClientEventFile($file, $class, $router);
        }
    }

    protected function callClientEventFile(string $file, string $class, Router $router): void
    {
        if (!file_exists($file)) {
            return;
        }
        require_once $file;
        if (!class_exists($class, false)) {
            return;
        }
        $instance = new $class();
        $instance->onRoute($router);
    }

    protected function loadMicroserviceServerRoutes(Router $router): void
    {
        $microDir = ROOT_PATH . 'Microservice/';
        if (!is_dir($microDir)) {
            return;
        }
        foreach (glob($microDir . '*/Server/Event/Route.php') ?: [] as $file) {
            // e.g. Microservice/Global/Server/Event/Route.php
            // → namespace Microservice\Global\Server\Event\Route
            $rel   = str_replace(ROOT_PATH, '', str_replace('\\', '/', $file));
            $class = '\\' . str_replace('/', '\\', rtrim($rel, '.php'));
            if (!file_exists($file)) continue;
            require_once $file;
            if (!class_exists($class, false)) continue;
            $instance = new $class();
            $result   = $instance->onRoute($router);
            if ($result instanceof Router) {
                $router = $result;
            }
        }
    }
}
