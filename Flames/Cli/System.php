<?php

namespace Flames\Cli;

use Flames\Cli\Command\Cache;
use Flames\Cli\Command\Coroutine;
use Flames\Cli\Command\Db;
use Flames\Cli\Command\Inject;
use Flames\Cli\Command\Install;
use Flames\Cli\Command\Key\Generate as KeyGenerate;
use Flames\Cli\Command\Crypto\Key\Generate as CryptoKeyGenerate;
use Flames\Cli\Command\Build\Assets;
use Flames\Cli\Command\Build\App\StaticEx;
use Flames\Cli\Command\Build\App\Native;
use Flames\Cli\Command\Build\App\Mobile;
use Flames\Cli\Command\Container;
use Flames\Cli\Command\MicroserviceList;
use Flames\Cli\Command\Package;
use Flames\Cli\Command\Route;
use Flames\Cli\Command\Schedules\Install       as SchedulesInstall;
use Flames\Cli\Command\Schedules\Remove        as SchedulesRemove;
use Flames\Cli\Command\Schedules\Run          as SchedulesRun;
use Flames\Cli\Command\Schedules\ListSchedules as SchedulesList;
use Flames\Cli\Command\Schedules\Show          as SchedulesShow;
use Flames\Cli\Command\Schedules\Stop          as SchedulesStop;
use Flames\Cli\Command\Server;
use Flames\Cli\Command\Shell;
use Flames\Collection\Arr;
use Flames\Event;
use Flames\Router;

/**
 * @internal
 */
final class System
{
    protected static $commands = [
        'install'            => Install::class,
        'inject'             => Inject::class,
        'key generate'       => KeyGenerate::class,
        'shell'              => Shell::class,
        'serve'              => Server::class,
        'surface build'      => Assets::class,
        'snapshot'           => StaticEx::class,
        'bundle'             => Native::class,
        'container'          => Container::class,
        'package'            => Package::class,
        'db'                 => Db::class,
        'schedule install'   => SchedulesInstall::class,
        'schedule remove'    => SchedulesRemove::class,
        'schedule run'       => SchedulesRun::class,
        'schedule list'      => SchedulesList::class,
        'schedule show'      => SchedulesShow::class,
        'schedule stop'      => SchedulesStop::class,
        'route server list'  => Route::class,
        'route client list'  => Route::class,
        'microservice list'  => MicroserviceList::class,
        'db wipe'            => Db::class,
        'db truncate'        => Db::class,
        'db migrate'         => Db::class,
        'cache purge'        => Cache::class,
        'cache purge kernel' => Cache::class,
        'cache purge all'    => Cache::class,
        'internal:coroutine' => Coroutine::class,
    ];

    // Passthrough commands flush ob and skip the Flames header
    protected static array $passthroughCommands = ['container', 'package', 'db', 'shell', 'cache'];

    // ── Help sections ─────────────────────────────────────────────────────────

    protected static array $frameworkHelp = [
        ['install',                     'Install the project'],
        ['install --nokey',             'Install without generating a unique key'],
        ['install --nocryptographykey', 'Install without generating a cryptography key'],
        ['install --noexample',         'Install without example project'],
        ['install --noinject',          'Install without injecting the global forge launcher'],
        ['inject',                      'Inject the global forge launcher'],
        ['key generate',                'Create or update the project unique key'],
        ['key generate --crypto',       'Create or update the cryptography key'],
        ['shell',                       'Open an interactive PHP REPL'],
    ];

    protected static array $scheduleHelp = [
        ['schedule install',           'Register schedule runner in crontab'],
        ['schedule remove',            'Remove schedule runner from crontab'],
        ['schedule run',               'Run all due schedules'],
        ['schedule list',              'List all schedules defined in config.yml'],
        ['schedule show',              'Show currently running schedule processes'],
        ['schedule stop {name|pid}',   'Stop a running schedule by name or PID'],
    ];

    protected static array $webserverHelp = [
        ['serve',               'Run a development server (0.0.0.0:80)'],
        ['serve {host}:{port}', 'Run at a specific host and port'],
        ['serve -host={host}',  'Run at a specific host'],
        ['serve -port={port}',  'Run at a specific port'],
    ];

    protected static array $surfaceHelp = [
        ['surface build', 'Build client-side assets'],
    ];

    protected static array $snapshotHelp = [
        ['snapshot',              'Build the app as static HTML pages'],
        ['snapshot --cloudflare', 'Build for Cloudflare Pages'],
    ];

    protected static array $bundleHelp = [
        ['bundle',                       'Build app webview for Linux or Windows'],
        ['bundle --linux',               'Build for Linux'],
        ['bundle --windows',             'Build for Windows'],
        ['bundle --windows --installer', 'Build Windows installer'],
        ['bundle --android',             'Build Android APK'],
    ];

    protected static array $containerHelp = [
        ['container',                      'Show running container status'],
        ['container run',                  'Start containers in the background'],
        ['container run --foreground',     'Start containers in the foreground'],
        ['container build',                'Build / rebuild container images'],
        ['container stop',                 'Stop and remove containers'],
        ['container compose {args}',       'Run any docker compose command'],
        ['container {service}',            'Open a bash shell in a container'],
        ['container {service} bash|sh',    'Open bash or sh in a container'],
        ['container {service} {command}',  'Run "php forge {command}" inside a container'],
        ['container {service} php {args}', 'Run an explicit php command inside a container'],
    ];

    protected static array $databaseHelp = [
        ['db',                           'Open a shell for the default database'],
        ['db {connection}',              'Open a shell for a named connection'],
        ['db sql {sql}',                 'Run SQL on the default database'],
        ['db sql {connection} {sql}',    'Run SQL on a named connection'],
        ['db model list',                'List all models across all connections'],
        ['db model list {connection}',   'List models for a specific connection'],
        ['db migrate',                   'Force-migrate all models'],
        ['db migrate {connection}',      'Force-migrate models for a specific connection'],
        ['db truncate',                  'Empty all tables, reset auto-increment'],
        ['db truncate {connection}',     'Truncate tables for a specific connection'],
        ['db wipe',                      'Drop all tables in the default database'],
        ['db wipe {connection}',         'Drop all tables in a specific connection'],
    ];

    protected static array $cacheHelp = [
        ['cache purge',        'Clear everything in app cache'],
        ['cache purge kernel', 'Clear kernel cache'],
        ['cache purge all',    'Clear everything'],
    ];

    protected static array $packageHelp = [
        ['package',                     'List available composer commands'],
        ['package require {package}',   'Add a new package to the project'],
        ['package remove {package}',    'Remove a package from the project'],
        ['package update',              'Update all project packages'],
        ['package update {package}',    'Update a specific package'],
        ['package show',                'Show installed packages'],
        ['package audit',               'Check for security vulnerabilities'],
        ['package validate',            'Validate composer.json'],
        ['package {command} {args}',    'Run any composer command'],
    ];

    protected static array $routeHelp = [
        ['route server list',                  'List all server-side routes'],
        ['route client list',                  'List all client-side routes'],
        ['route server list {microservice}',   'List server-side routes for a microservice'],
        ['route client list {microservice}',   'List client-side routes for a microservice'],
    ];

    protected static array $microserviceHelp = [
        ['microservice list', 'List all configured microservices'],
    ];

    protected Arr $data;
    protected bool $debug;

    public function __construct(Arr $data = null, bool $debug = true)
    {
        $this->debug = $debug;

        if ($data === null) {
            $this->data = Data::getData();
            return;
        }

        $this->data = $data;
    }

    public function run(): bool
    {
        $command = (string)($this->data->command ?? '');
        $args    = array_values((array)$this->data->argument);
        $options = array_values((array)$this->data->option);

        // ── Multi-word command resolution ─────────────────────────────────────
        // Try longest match first: command + 2 args, then + 1 arg, then alone.
        $resolved = null;
        $consumed = 0;

        foreach ([3, 2, 1, 0] as $n) {
            if ($n > 0 && !isset($args[$n - 1])) {
                continue;
            }
            $parts = [$command];
            for ($i = 0; $i < $n; $i++) {
                $parts[] = $args[$i];
            }
            $candidate = implode(' ', $parts);
            if (isset(self::$commands[$candidate])) {
                $resolved = $candidate;
                $consumed = $n;
                break;
            }
        }

        if ($resolved === null) {
            // Fallback: Docker service shortcut (forge {service} [args])
            if ($command !== '' && Container::serviceExists($command)) {
                return $this->runContainerService($command);
            }
            $this->dispatchHelper();
            return false;
        }

        // Update data: expose the full resolved command and remaining arguments
        $this->data->command  = $resolved;
        $this->data->argument = Arr(array_slice($args, $consumed));

        if ($resolved === 'internal:coroutine') {
            $this->debug = false;
        }

        // ── Special routing ───────────────────────────────────────────────────

        // bundle --android → Mobile class
        if ($resolved === 'bundle' && in_array('android', $options, true)) {
            $instance = new Mobile($this->data);
            if ($this->debug) {
                Output::logo();
                Output::blank();
                echo Output::CYAN . Output::BOLD . '  Running ' . Output::RESET
                    . Output::GREEN . Output::BOLD . 'bundle --android' . Output::RESET . "\n\n";
            }
            $return = $instance->run($this->debug);
            if ($this->debug) Output::blank();
            return $return;
        }

        // key generate --crypto → CryptoKeyGenerate class
        if ($resolved === 'key generate' && in_array('crypto', $options, true)) {
            $instance = new CryptoKeyGenerate($this->data);
            if ($this->debug) {
                Output::logo();
                Output::blank();
                echo Output::CYAN . Output::BOLD . '  Running ' . Output::RESET
                    . Output::GREEN . Output::BOLD . 'key generate --crypto' . Output::RESET . "\n\n";
            }
            $return = $instance->run($this->debug);
            if ($this->debug) Output::blank();
            return $return;
        }

        // ── Standard dispatch ─────────────────────────────────────────────────
        $isPassthrough = in_array($command, self::$passthroughCommands, true);

        if ($this->debug === true && $isPassthrough === false) {
            Output::logo();
            Output::blank();
            echo Output::CYAN . Output::BOLD
                . '  Running ' . Output::RESET
                . Output::GREEN . Output::BOLD . $resolved . Output::RESET
                . "\n\n";
        }

        $instance = new self::$commands[$resolved]($this->data);
        $return   = $instance->run($this->debug);

        if ($this->debug === true && $isPassthrough === false) {
            Output::blank();
        }

        return $return;
    }

    /**
     * Handles "forge {service} [args]" — shortcut for container exec.
     */
    protected function runContainerService(string $service): bool
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $args     = array_values(array_slice($_SERVER['argv'], 2));
        $instance = new Container($this->data);
        return $instance->runExec($service, $args);
    }

    protected function dispatchHelper(): void
    {
        Output::logo();
        Output::blank();

        // ── Execution mode banner ─────────────────────────────────────────────
        if (file_exists('/.dockerenv')) {
            $hostname = trim((string)shell_exec('hostname 2>/dev/null')) ?: 'container';
            echo '  ' . Output::GRAY . 'Running in ' . Output::RESET
                . Output::CYAN . Output::BOLD . 'container' . Output::RESET
                . Output::GRAY . '  (' . $hostname . ')' . Output::RESET . "\n";
        } else {
            echo '  ' . Output::GRAY . 'Running in ' . Output::RESET
                . Output::GREEN . Output::BOLD . 'native' . Output::RESET . "\n";
        }

        Output::blank();
        echo '  ' . Output::WHITE . Output::BOLD . 'USAGE' . Output::RESET . "\n";
        echo '    ' . Output::GRAY . 'forge ' . Output::RESET
            . Output::CYAN . '<command>' . Output::RESET
            . Output::GRAY . ' [--native] [--container] [options]' . Output::RESET . "\n";

        Output::blank();
        echo '  ' . Output::WHITE . Output::BOLD . 'GLOBAL FLAGS' . Output::RESET . "\n";
        Output::command('--native',    'Force execution on the local machine (skip Docker routing)');
        Output::command('--container', 'Force execution inside the Docker container');

        // Application CLI routes (first)
        $cliRoutes = $this->getApplicationCliRoutes();
        if (!empty($cliRoutes)) {
            Output::section('Application Commands');
            foreach ($cliRoutes as $route) {
                Output::command($route, 'CLI route');
            }
        }

        Output::section('Framework Commands');
        foreach (self::$frameworkHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::section('Schedules');
        foreach (self::$scheduleHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::section('Webserver (Development)');
        foreach (self::$webserverHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::section('Surface (PHP Frontend WASM)');
        foreach (self::$surfaceHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::section('Snapshot (Build Static App)');
        foreach (self::$snapshotHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::section('Bundle (Build Native App)');
        foreach (self::$bundleHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::section('Container (Docker)');
        foreach (self::$containerHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::section('Database');
        foreach (self::$databaseHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::section('Packages (Composer)');
        foreach (self::$packageHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::section('Routes');
        foreach (self::$routeHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::section('Microservices');
        foreach (self::$microserviceHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::section('Cache');
        foreach (self::$cacheHelp as [$cmd, $desc]) {
            Output::command($cmd, $desc);
        }

        Output::blank();
    }

    /**
     * Dispatches the Route event and returns a list of CLI route names.
     */
    protected function getApplicationCliRoutes(): array
    {
        try {
            $router = Event::dispatch('Route', 'onRoute', new Router());
        } catch (\Throwable $e) {
            return [];
        }

        if ($router === null) {
            return [];
        }

        $routes = $router->getMetadata();
        $names  = [];

        foreach ($routes as $route) {
            if ($route->methods === 'CLI') {
                $names[] = $route->routeFormatted;
            }
        }

        return $names;
    }
}
