<?php
declare(strict_types=1);


namespace Flames\Framework;

use Flames\Framework\Controller\RequestData;
use Flames\Microservice\Microservice;

/**
 * @internal
 */
final class Event
{
    private static string $context = '';

    private static ?string $basePath = null;

    private static ?string $namespace = null;

    /** @var array<string, ?string> */
    private static array $classes = [];

    /** @var array<string, object> */
    private static array $instances = [];

    public static function dispatch(mixed ...$args): RequestData|bool|string|null
    {
        [$contract, $offset] = self::parseContract($args);
        $event = (string) ($args[$offset] ?? '');

        if ($event === '') {
            return null;
        }

        $next = $args[$offset + 1] ?? null;
        $action = is_string($next) ? $next : null;
        $instance = self::resolve($event, $contract);

        if ($instance === null) {
            return null;
        }

        if ($action === null) {
            return $instance;
        }

        return $instance->{$action}(...self::params($args, $offset, $action));
    }

    /** @return array{0: ?string, 1: int} */
    private static function parseContract(array $args): array
    {
        if (!isset($args[0]) || !is_string($args[0]) || !interface_exists($args[0])) {
            return [null, 0];
        }

        return [$args[0], 1];
    }

    /** @param array<int, mixed> $args */
    private static function params(array $args, int $offset, string $action): array
    {
        $start = $offset + 2;

        return $start >= count($args) ? [] : array_slice($args, $start);
    }

    private static function resolve(string $event, ?string $contract): ?object
    {
        self::syncContext();
        $class = self::classFor($event);

        if ($class === null) {
            return null;
        }

        $instance = self::$instances[$class] ??= new $class();

        if ($contract !== null && !$instance instanceof $contract) {
            throw new \RuntimeException(sprintf('%s must implement %s', $class, $contract));
        }

        return $instance;
    }

    private static function classFor(string $event): ?string
    {
        if (array_key_exists($event, self::$classes)) {
            return self::$classes[$event];
        }

        $path = self::basePath() . $event . '.php';

        if (!is_file($path)) {
            return self::$classes[$event] = null;
        }

        return self::$classes[$event] = '\\' . self::namespace() . 'Server\\Event\\' . $event;
    }

    private static function syncContext(): void
    {
        $context = Microservice::get();

        if (self::$context === $context) {
            return;
        }

        self::$context = $context;
        self::$basePath = null;
        self::$namespace = null;
        self::$classes = [];
        self::$instances = [];
    }

    private static function basePath(): string
    {
        return self::$basePath ??= Microservice::getPath() . 'Server/Event/';
    }

    private static function namespace(): string
    {
        return self::$namespace ??= Microservice::getNamespace();
    }
}
