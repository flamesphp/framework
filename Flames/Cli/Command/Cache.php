<?php

namespace Flames\Cli\Command;

use Flames\Cli\Output;

/**
 * @internal
 *
 * Cache management commands.
 *
 * Usage:
 *   forge cache purge        — clear .cache (everything except .cache/.flames)
 *   forge cache purge kernel — clear .cache/.flames (except environment and schedules)
 *   forge cache purge all    — clear everything including .cache/.flames
 */
final class Cache
{
    protected string $mode;

    public function __construct($data)
    {
        $resolved = (string)($data->command ?? '');
        $this->mode = match ($resolved) {
            'cache purge kernel' => 'kernel',
            'cache purge all'    => 'all',
            default              => 'purge',
        };
    }

    public function run(bool $debug = false): bool
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $cacheDir  = ROOT_PATH . '.cache/';
        $flamesDir = $cacheDir . '.flames/';

        switch ($this->mode) {
            case 'purge':
                $count = $this->clearDir($cacheDir, ['.flames']);
                echo Output::GREEN . "  Cache purged ($count items cleared)." . Output::RESET . "\n";
                break;

            case 'kernel':
                $count = $this->clearDir($flamesDir, ['environment', 'schedules']);
                echo Output::GREEN . "  Kernel cache purged ($count items cleared)." . Output::RESET . "\n";
                break;

            case 'all':
                $countFlames = $this->clearDir($flamesDir, []);
                $countCache  = $this->clearDir($cacheDir, ['.flames']);
                $total = $countFlames + $countCache;
                echo Output::GREEN . "  All cache purged ($total items cleared)." . Output::RESET . "\n";
                break;
        }

        return true;
    }

    /**
     * Recursively deletes all entries inside $dir, skipping names listed in $skip.
     * Returns the count of top-level items removed.
     */
    protected function clearDir(string $dir, array $skip): int
    {
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            if (in_array($entry, $skip, true)) continue;

            $path = $dir . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
            $count++;
        }

        return $count;
    }

    protected function removeDir(string $dir): void
    {
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
