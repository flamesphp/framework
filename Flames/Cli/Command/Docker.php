<?php

namespace Flames\Cli\Command;

use Flames\Cli\Output;

/**
 * @internal
 */
final class Docker
{
    // Arguments after "docker" (raw, unprocessed)
    protected array $args = [];

    public function __construct($data)
    {
        // Skip argv[0] (script path) and argv[1] (the "docker" command)
        $this->args = array_values(array_slice($_SERVER['argv'], 2));
    }

    /**
     * docker compose base command — forces ANSI colour output regardless of
     * whether PHP's passthru() is connected to a real TTY.
     * CLICOLOR_FORCE=1 covers any sub-process that respects it; --ansi always
     * is the Docker Compose v2 flag that does the same at the compose level.
     */
    private const COMPOSE = 'CLICOLOR_FORCE=1 TERM=xterm-256color docker compose --ansi always';

    public function run(bool $debug = false): bool
    {
        // Flush and discard the Kernel's ob_start() buffer so that passthru()
        // output appears in the terminal immediately and in the correct order.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // docker compose must run from the directory containing docker-compose.yml
        chdir(ROOT_PATH);

        if (self::isInstalled() === false) {
            Output::error('Docker is not installed or not found in PATH.');
            return false;
        }

        if (empty($this->args) === true) {
            self::showStatus();
            return true;
        }

        $first = $this->args[0];

        // ── docker compose * ─────────────────────────────────────────────
        if ($first === 'compose') {
            return $this->runCompose(array_slice($this->args, 1));
        }

        // ── docker {service} [args] ──────────────────────────────────────
        return $this->runExec($first, array_slice($this->args, 1));
    }

    // ─────────────────────────────────────────────────────────────────────
    // docker compose *
    // ─────────────────────────────────────────────────────────────────────

    protected function runCompose(array $args): bool
    {
        $cmd = self::COMPOSE . ' ' . implode(' ', $args);
        passthru($cmd, $code);
        return $code === 0;
    }

    // ─────────────────────────────────────────────────────────────────────
    // docker {service} [args]
    //
    // Rules:
    //   no args           → docker compose exec {service} bash
    //   bash / sh         → docker compose exec {service} {shell}
    //   php bin *         → docker compose exec {service} php bin {rest}  (explicit)
    //   {anything else}   → docker compose exec {service} php bin {rest}  (implicit)
    // ─────────────────────────────────────────────────────────────────────

    protected function runExec(string $service, array $args): bool
    {
        if (self::serviceExists($service) === false) {
            Output::error("Service '{$service}' not found in docker-compose.yml.");
            return false;
        }

        $inner = $this->buildInnerCommand($args);
        $cmd   = self::COMPOSE . ' exec ' . escapeshellarg($service) . ' ' . $inner;
        passthru($cmd, $code);
        return $code === 0;
    }

    protected function buildInnerCommand(array $args): string
    {
        if (empty($args) === true) {
            return 'bash';
        }

        $first = $args[0];

        // Shell shortcut
        if ($first === 'bash' || $first === 'sh') {
            return $first;
        }

        // Explicit php — pass through as-is
        if ($first === 'php') {
            return implode(' ', array_map('escapeshellarg', $args));
        }

        // Implicit: prepend "php bin"
        return 'php bin ' . implode(' ', array_map('escapeshellarg', $args));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    protected static function isInstalled(): bool
    {
        exec('docker --version 2>/dev/null', $out, $code);
        return $code === 0;
    }

    protected static function showStatus(): void
    {
        passthru(self::COMPOSE . ' ps');
    }

    protected static function serviceExists(string $service): bool
    {
        $composePath = ROOT_PATH . 'docker-compose.yml';
        if (file_exists($composePath) === false) {
            return false;
        }

        // Ask docker compose for the list of configured services
        exec(self::COMPOSE . ' config --services 2>/dev/null', $services, $code);
        if ($code !== 0) {
            // Fallback: grep service name from docker-compose.yml
            return (bool)preg_match('/^\s{2,4}' . preg_quote($service, '/') . '\s*:/m',
                file_get_contents($composePath));
        }

        return in_array($service, $services, true);
    }
}
