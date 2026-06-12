<?php
declare(strict_types=1);


namespace Flames\Kernel;

/**
 * Shared forge entry-point logic.
 *
 * Handles --native / --container flag parsing, Docker detection & fast routing,
 * then delegates to the Flames Kernel.  Used by every "forge" entry-point file
 * so the same code path runs for both composer and no-composer installs.
 *
 * @internal
 */
final class Forge
{
    /**
     * Boot the forge entry point.
     *
     * @param string $projectRoot  Absolute path to the project / framework root.
     * @param string $kernelFile   Path to Kernel.php relative to $projectRoot.
     */
    public static function boot(string $projectRoot, string $kernelFile): void
    {
        // ── 1. Parse routing flags ────────────────────────────────────────────
        $rawArgs        = array_slice($_SERVER['argv'], 1);
        $forceNative    = false;
        $forceContainer = false;
        $filteredArgs   = [];

        foreach ($rawArgs as $arg) {
            if ($arg === '--native')    { $forceNative    = true; continue; }
            if ($arg === '--container') { $forceContainer = true; continue; }
            $filteredArgs[] = $arg;
        }

        $command = $filteredArgs[0] ?? null;

        // These commands must always run locally (interactive TTY or local-only):
        // 'container' manages Docker itself, 'db'/'shell' need a proper TTY
        $localOnly = ['container', 'package', 'db', 'shell'];

        // ── 2. Docker routing ─────────────────────────────────────────────────
        if ($forceNative === false && in_array($command, $localOnly, true) === false) {
            if ($forceContainer === true || self::dockerIsRunning()) {
                $container = self::getAppContainer($projectRoot);
                if ($container !== null) {
                    // Use "docker exec" (no compose-file parsing = much faster)
                    $innerArgs = implode(' ', array_map('escapeshellarg', $filteredArgs));
                    passthru(
                        'docker exec -it ' . escapeshellarg($container) . ' php forge ' . $innerArgs,
                        $exitCode
                    );
                    exit($exitCode ?? 0);
                }
            }
        }

        // ── 3. Restore filtered argv (flags consumed) ─────────────────────────
        $_SERVER['argv'] = array_merge([$_SERVER['argv'][0]], $filteredArgs);
        $_SERVER['argc'] = count($_SERVER['argv']);

        // ── 4. Load kernel ────────────────────────────────────────────────────
        chdir($projectRoot);
        require $projectRoot . DIRECTORY_SEPARATOR . $kernelFile;
        \Flames\Kernel::run();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Docker helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns true when the Docker daemon is reachable and we are NOT already
     * inside a container ourselves.
     *
     * Uses a socket-file check instead of spawning "docker info", which can
     * take ~1 s even when Docker is healthy.  The Unix domain socket is
     * created by dockerd on start and removed on stop, so its presence is a
     * reliable, near-instant proxy for "daemon is running".
     */
    private static function dockerIsRunning(): bool
    {
        if (file_exists('/.dockerenv') === true) {
            return false; // already inside a container — never self-route
        }

        // Typical socket locations on Linux and macOS (Docker Desktop uses a symlink)
        foreach (['/var/run/docker.sock', '/run/docker.sock'] as $sock) {
            if (file_exists($sock)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the name of the best-matching running container for this project,
     * or null if none is found.
     *
     * Uses "docker ps" with a project-slug name-filter so Docker does the
     * heavy filtering and we only receive relevant lines.
     */
    private static function getAppContainer(string $projectRoot): ?string
    {
        $composePath = $projectRoot . DIRECTORY_SEPARATOR . 'docker-compose.yml';
        if (file_exists($composePath) === false) {
            return null;
        }

        // Normalise the project directory name to match docker's naming convention
        $projectSlug = strtolower(basename($projectRoot));
        $projectSlug = preg_replace('/[^a-z0-9]/', '', $projectSlug);

        // Pass the slug directly to docker ps --filter so the daemon does the
        // filtering; we receive far fewer lines than an unfiltered listing.
        exec(
            'docker ps --format "{{.Names}}"'
            . ' --filter "status=running"'
            . ' --filter ' . escapeshellarg('name=' . $projectSlug)
            . ' 2>/dev/null',
            $names,
            $code
        );

        if ($code !== 0) {
            return null;
        }

        $names = array_values(array_filter($names, fn($n) => trim($n) !== ''));
        if (empty($names)) {
            return null;
        }

        // 1st pass: project slug + known app-service keywords
        foreach ($names as $name) {
            $n = strtolower($name);
            if (str_contains($n, $projectSlug) &&
                (str_contains($n, 'apache') || str_contains($n, '-app') || str_contains($n, 'php'))) {
                return $name;
            }
        }

        // 2nd pass: any container belonging to this project
        foreach ($names as $name) {
            if (str_contains(strtolower($name), $projectSlug)) {
                return $name;
            }
        }

        return null;
    }
}
