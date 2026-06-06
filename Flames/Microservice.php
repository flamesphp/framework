<?php
declare(strict_types=1);


namespace Flames;

/**
 * Class Microservice
 *
 * Manages the active microservice context. The default microservice is 'App',
 * which maps to the standard App/ directory with the App\ namespace.
 *
 * When a named microservice is active (e.g. 'Admin'), classes are loaded from
 * App/Microservice/Admin/ and use the App\Microservice\Admin\ namespace prefix.
 *
 * .env configuration example:
 *   APP_MICROSERVICES=Admin,Painel,Landing
 *   APP_MICROSERVICES_ADMIN=admin.domain.com,admin.domain.local
 *   APP_MICROSERVICES_PAINEL=painel.domain.com,*.painel.local
 *   APP_MICROSERVICES_LANDING=domain.com,domain.local
 *
 * Programmatic override (e.g. inside an event handler):
 *   \Flames\Microservice::set('Painel');
 */
final class Microservice
{
    protected static string $current = 'App';

    /**
     * Resolves the active microservice by matching the current HTTP host against
     * patterns defined in config.yml (microservices section).
     * Each pattern supports exact hosts and wildcards (e.g. *.domain.com).
     * Skipped in CLI mode.
     *
     * config.yml example:
     *   microservices:
     *     admin:
     *       namespace: "Admin"
     *       hosts:
     *         - "admin.domain.com"
     *         - "*.admin.local"
     *
     * @return void
     */
    public static function resolve(): void
    {
        if (\Flames\Forge\Cli::isCli() === true) {
            return;
        }

        $config = Kernel\Config::get();
        if (empty($config['microservices']) === true) {
            return;
        }

        $host = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? null;
        if (empty($host) === true) {
            return;
        }

        // Strip port if present (e.g. localhost:8080 → localhost)
        if (str_contains($host, ':') === true) {
            $host = explode(':', $host)[0];
        }

        foreach ($config['microservices'] as $entry) {
            $namespace = $entry['namespace'] ?? null;
            $hosts     = $entry['hosts']     ?? [];

            if (empty($namespace) === true || empty($hosts) === true) {
                continue;
            }

            foreach ($hosts as $pattern) {
                if (empty($pattern) === true) {
                    continue;
                }

                // fnmatch supports both exact hosts and wildcards (e.g. *.domain.com)
                if (fnmatch($pattern, $host) === true) {
                    self::activate($namespace);
                    return;
                }
            }
        }
    }

    /**
     * Programmatically sets the active microservice.
     * Use 'App' to revert to the default context.
     *
     * @param string $name Microservice name (matches directory under App/Microservice/)
     *                     or 'App' for the default application context.
     * @return void
     */
    public static function set(string $name): void
    {
        self::activate($name);
    }

    /**
     * Returns the currently active microservice name.
     * Defaults to 'App' when no microservice is active.
     *
     * @return string
     */
    public static function get(): string
    {
        return self::$current;
    }

    /**
     * Returns true when the default App context is active (no named microservice).
     *
     * @return bool
     */
    public static function isDefault(): bool
    {
        return self::$current === 'App';
    }

    /**
     * Returns the absolute filesystem path for the active microservice base directory.
     *
     * Default ('App') → ROOT_PATH . 'App/'          (standard App/ directory)
     * Named ('Test')  → ROOT_PATH . 'Microservice/Test/'  (root-level, no App/ prefix)
     *
     * @return string
     */
    public static function getPath(): string
    {
        if (self::$current === 'App') {
            return APP_PATH;
        }

        return ROOT_PATH . 'Microservice/' . self::$current . '/';
    }

    /**
     * Returns the PHP namespace prefix (with trailing backslash) for the active microservice.
     *
     * Default ('App') → 'App\'
     * Named ('Test')  → 'Microservice\Test\'
     *
     * @return string
     */
    public static function getNamespace(): string
    {
        if (self::$current === 'App') {
            return 'App\\';
        }

        return 'Microservice\\' . self::$current . '\\';
    }

    /**
     * Activates a microservice by name and refreshes the AutoLoad event flag
     * when the microservice ships its own Server/Event/Load.php.
     *
     * @param string $name
     * @return void
     */
    protected static function activate(string $name): void
    {
        self::$current = $name;

        if ($name === 'App') {
            $eventPath = APP_PATH . 'Server/Event/Load.php';
        } else {
            $eventPath = ROOT_PATH . 'Microservice/' . $name . '/Server/Event/Load.php';
        }

        if (file_exists($eventPath) === true) {
            AutoLoad::$event = true;
        }
    }
}
