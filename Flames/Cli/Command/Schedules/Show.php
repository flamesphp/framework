<?php

namespace Flames\Cli\Command\Schedules;

use Flames\Cli\Output;
use Flames\Kernel\Config;
use Flames\Server\Process;

/**
 * @internal
 */
final class Show
{
    public function __construct($data) {}

    public function run(bool $debug = false): bool
    {
        $config    = Config::get();
        $schedules = $config['schedules'] ?? [];

        Output::section('Schedule Status');

        if (empty($schedules) === true) {
            Output::warning('No schedules defined in config.yml.');
            Output::blank();
            return true;
        }

        $cacheDir = ROOT_PATH . '.cache/.flames/schedules/';
        $now      = time();

        $col = [18, 14, 14, 8, 11];
        self::row(['NAME', 'LAST RUN', 'NEXT RUN', 'PID', 'STATUS'], $col, true);
        self::divider($col);

        foreach ($schedules as $name => $schedule) {
            $every    = $schedule['run']['every'] ?? [];
            $interval = Run::calculateInterval($every);

            $cacheFile = $cacheDir . $name . '.json';
            $lastRun   = null;
            if (file_exists($cacheFile) === true) {
                $cached  = json_decode(file_get_contents($cacheFile), true);
                $lastRun = $cached['last_run'] ?? null;
            }

            $lastRunLabel = $lastRun !== null ? self::formatElapsed($now - $lastRun) : 'never';
            $nextRunLabel = ($lastRun !== null && $interval > 0)
                ? self::formatNext(($lastRun + $interval) - $now)
                : ($interval > 0 ? 'now' : '—');

            [$pids, $recentlyDone] = self::getLockStatus($cacheDir, $name, $now);
            $pidStr = empty($pids) ? '—' : implode(',', $pids);

            if (empty($pids) === false) {
                $status = Output::GREEN . Output::BOLD . 'running' . Output::RESET;
            } elseif ($recentlyDone) {
                $status = Output::CYAN . 'completed' . Output::RESET;
            } else {
                $status = Output::GRAY . 'idle' . Output::RESET;
            }

            self::row([$name, $lastRunLabel, $nextRunLabel, $pidStr, $status], $col);
        }

        Output::blank();
        return true;
    }

    /**
     * Returns [$runningPids, $recentlyCompleted].
     * Lock files for dead processes are kept for 30 s then cleaned up.
     */
    protected static function getLockStatus(string $cacheDir, string $name, int $now): array
    {
        $running      = [];
        $recentlyDone = false;

        foreach (glob($cacheDir . $name . '.*.lock') ?: [] as $lockFile) {
            $data       = json_decode(file_get_contents($lockFile), true);
            $pid        = (int)($data['pid']        ?? 0);
            $startedAt  = (int)($data['started_at'] ?? 0);

            if ($pid > 0 && Process::isRunning($pid) === true) {
                $running[] = $pid;
            } else {
                // Keep the lock file visible for 30 s so the user can see
                // "completed" before it disappears.
                if (($now - $startedAt) < 30) {
                    $recentlyDone = true;
                } else {
                    @unlink($lockFile);
                }
            }
        }

        return [$running, $recentlyDone];
    }

    protected static function formatElapsed(int $seconds): string
    {
        if ($seconds < 0)    return 'just now';
        if ($seconds < 60)   return $seconds . 's ago';
        if ($seconds < 3600) return floor($seconds / 60) . 'm ago';
        return floor($seconds / 3600) . 'h ago';
    }

    protected static function formatNext(int $seconds): string
    {
        if ($seconds <= 0)   return 'now';
        if ($seconds < 60)   return 'in ' . $seconds . 's';
        if ($seconds < 3600) return 'in ' . floor($seconds / 60) . 'm';
        return 'in ' . floor($seconds / 3600) . 'h';
    }

    protected static function row(array $cells, array $widths, bool $header = false): void
    {
        $color = $header ? Output::YELLOW . Output::BOLD : Output::WHITE;
        $line  = '  ';
        foreach ($cells as $i => $cell) {
            $w    = $widths[$i] ?? 14;
            // Strip ANSI codes for padding calculation
            $plain = preg_replace('/\033\[[0-9;]*m/', '', (string)$cell);
            $pad   = max(0, $w - strlen($plain));
            $line .= $color . $cell . Output::RESET . str_repeat(' ', $pad + 2);
        }
        echo $line . "\n";
    }

    protected static function divider(array $widths): void
    {
        $line = '  ';
        foreach ($widths as $w) {
            $line .= Output::GRAY . str_repeat('─', $w) . Output::RESET . '  ';
        }
        echo $line . "\n";
    }
}
