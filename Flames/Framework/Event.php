<?php
declare(strict_types=1);


namespace Flames\Framework;

use Flames\Framework\Controller\RequestData;
use Flames\Microservice\Microservice;

/**
 * The Event class handles the dispatching of events.
 *
 * @internal
 */
final class Event
{
    /**
     * Dispatches an event and executes the corresponding action.
     *
     * The event class and its file path are derived from the active microservice context:
     *
     *   Default ('App'):
     *     class → App\Server\Event\{Event}
     *     file  → App/Server/Event/{Event}.php
     *
     *   Microservice ('Admin'):
     *     class → App\Microservice\Admin\Server\Event\{Event}
     *     file  → App/Microservice/Admin/Server/Event/{Event}.php
     *
     * When the first argument is an interface FQCN, it is used as a contract
     * that the resolved event class must implement:
     *
     *   Event::dispatch(RouteContract::class, 'Route', 'onRoute')
     *
     * Without a contract, the legacy form is still supported:
     *
     *   Event::dispatch('Route', 'onRoute')
     *
     * @param mixed ...$args Event name, optional contract, action, and parameters.
     * @return RequestData|bool|string|null Returns RequestData, boolean value,
     *   string, or null based on the dispatched event and executed action.
     */
    public static function dispatch(mixed ...$args): RequestData|bool|string|null
    {
        $contract = null;
        $offset   = 0;

        if (isset($args[0]) && is_string($args[0]) && interface_exists($args[0])) {
            $contract = $args[0];
            $offset   = 1;
        }

        $event = (string) ($args[$offset] ?? '');
        if ($event === '') {
            return null;
        }

        $next   = $args[$offset + 1] ?? null;
        $action = is_string($next) ? $next : null;
        $params = $action !== null
            ? array_slice($args, $offset + 2)
            : ($next !== null ? array_slice($args, $offset + 1) : []);

        $path = Microservice::getPath() . 'Server/Event/' . $event . '.php';
        if (file_exists($path) !== true) {
            return null;
        }

        $class    = '\\' . Microservice::getNamespace() . 'Server\\Event\\' . $event;
        $instance = new $class();

        if ($contract !== null && !$instance instanceof $contract) {
            throw new \RuntimeException(
                sprintf('%s must implement %s', $class, $contract)
            );
        }

        if ($action === null) {
            return $instance;
        }

        return $instance->{$action}(...$params);
    }
}
