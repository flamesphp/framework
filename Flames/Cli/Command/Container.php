<?php

namespace Flames\Cli\Command;

use Flames\Cli\Output;

/**
 * @internal
 */
final class Container
{
    // Arguments after "container" (raw, unprocessed)
    protected array $args = [];

    public function __construct($data)
    {
        // Skip argv[0] (script path) and argv[1] (the "container" command)
        $this->args = array_values(array_slice($_SERVER['argv'], 2));
    }

    // CLICOLOR_FORCE=1 + TERM tell docker compose to emit ANSI colours even
    // when it can't detect a TTY itself (e.g. when called via PHP passthru).
    // We deliberately avoid --ansi always because that flag causes docker compose
    // to try to allocate a console PTY, which fails in many environments.
    private const COMPOSE = 'CLICOLOR_FORCE=1 TERM=xterm-256color docker compose';

    public function run(bool $debug = false): bool
    {
        // Flush and discard the Kernel's ob_start() buffer so that passthru()
        // output appears in the terminal immediately and in the correct order.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        chdir(ROOT_PATH);

        if (self::isInstalled() === false) {
            Output::error('Docker is not installed or not found in PATH.');
            return false;
        }

        if (empty($this->args) === true) {
            self::showStatus();
            return true;
        }

        $first   = $this->args[0];
        $options = array_slice($this->args, 1);

        // ── forge container compose * ─────────────────────────────────────────
        if ($first === 'compose') {
            return $this->runCompose($options);
        }

        // ── shorthand lifecycle commands ──────────────────────────────────────
        if ($first === 'run') {
            $foreground = in_array('--foreground', $options, true);
            return $this->runCompose($foreground ? ['up'] : ['up', '-d']);
        }

        if ($first === 'build') {
            return $this->runCompose(['build']);
        }

        if ($first === 'stop') {
            return $this->runCompose(['down']);
        }

        // ── forge container {service} [args] ──────────────────────────────────
        return $this->runExec($first, $options);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // forge container compose *
    // ─────────────────────────────────────────────────────────────────────────

    protected function runCompose(array $args): bool
    {
        $cmd = self::COMPOSE . ' ' . implode(' ', $args);
        passthru($cmd, $code);
        return $code === 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // forge container {service} [args]
    // forge {service} [args]          (called as fallback from System)
    //
    // Rules:
    //   no args           → docker compose exec {service} bash
    //   bash / sh         → docker compose exec {service} {shell}
    //   php forge *       → docker compose exec {service} php forge {rest}  (explicit)
    //   {anything else}   → docker compose exec {service} php forge {rest}  (implicit)
    // ─────────────────────────────────────────────────────────────────────────

    public function runExec(string $service, array $args): bool
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

        if ($first === 'bash' || $first === 'sh') {
            return $first;
        }

        // Explicit php — pass through as-is
        if ($first === 'php') {
            return implode(' ', array_map('escapeshellarg', $args));
        }

        // Implicit: prepend "php forge"
        return 'php forge ' . implode(' ', array_map('escapeshellarg', $args));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    protected static function isInstalled(): bool
    {
        exec('docker --version 2>/dev/null', $out, $code);
        return $code === 0;
    }

    protected static function showStatus(): void
    {
        passthru(self::COMPOSE . ' ps');
    }

    public static function serviceExists(string $service): bool
    {
        $composePath = ROOT_PATH . 'docker-compose.yml';
        if (file_exists($composePath) === false) {
            return false;
        }

        exec(self::COMPOSE . ' config --services 2>/dev/null', $services, $code);
        if ($code !== 0) {
            return (bool)preg_match(
                '/^\s{2,4}' . preg_quote($service, '/') . '\s*:/m',
                file_get_contents($composePath)
            );
        }

        return in_array($service, $services, true);
    }
}
