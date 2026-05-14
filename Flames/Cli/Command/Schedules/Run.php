<?php

namespace Flames\Cli\Command\Schedules;

use Flames\Cli\Output;
use Flames\Kernel\Config;
use Flames\Server\Os;
use Flames\Server\Process; // used only for isRunning()

/**
 * @internal
 */
final class Run
{
    // How long (seconds) the command loops before exiting, so cron can call
    // it every minute and sub-minute schedules are handled inside the window.
    const LOOP_DURATION = 59;

    public function __construct($data) {}

    public function run(bool $debug = false): bool
    {
        $config    = Config::get();
        $schedules = $config['schedules'] ?? [];

        if (empty($schedules) === true) {
            if ($debug === true) {
                Output::info('No schedules configured.');
            }
            return true;
        }

        $cacheDir = ROOT_PATH . '.cache/.flames/schedules/';
        if (is_dir($cacheDir) === false) {
            $mask = umask(0);
            mkdir($cacheDir, 0777, true);
            umask($mask);
        }

        if (self::hasSubMinuteSchedules($schedules) === true) {
            $start = time();
            while ((time() - $start) < self::LOOP_DURATION) {
                $tickStart = microtime(true);
                $this->tick($schedules, $cacheDir, $debug);
                $elapsed = microtime(true) - $tickStart;
                $sleep   = max(0, 1_000_000 - (int)($elapsed * 1_000_000));
                if ($sleep > 0) {
                    usleep($sleep);
                }
            }
        } else {
            $this->tick($schedules, $cacheDir, $debug);
        }

        return true;
    }

    protected function tick(array $schedules, string $cacheDir, bool $debug): void
    {
        $now    = time();
        $phpBin = PHP_BINARY;
        $bin    = ROOT_PATH . 'forge';

        foreach ($schedules as $name => $schedule) {
            $command     = $schedule['command']           ?? null;
            $every       = $schedule['run']['every']      ?? [];
            $overlapping = $schedule['overlapping']       ?? true;
            $timeout     = $schedule['timeout']['seconds'] ?? null;

            if (empty($command) === true || empty($every) === true) {
                continue;
            }

            $interval = self::calculateInterval($every);
            if ($interval <= 0) {
                continue;
            }

            $cacheFile = $cacheDir . $name . '.json';
            $lastRun   = 0;

            if (file_exists($cacheFile) === true) {
                $cached  = json_decode(file_get_contents($cacheFile), true);
                $lastRun = $cached['last_run'] ?? 0;
            }

            if (($now - $lastRun) < $interval) {
                continue;
            }

            // overlapping=false: skip if a process for this schedule is alive
            if ($overlapping === false && self::hasRunningLocks($cacheDir, $name) === true) {
                if ($debug === true) {
                    Output::warning("Schedule '{$name}' skipped — overlapping=false and still running.");
                }
                continue;
            }

            // Persist timestamp BEFORE launching so a slow command does not
            // cause duplicate executions on the next cron / loop tick.
            file_put_contents($cacheFile, json_encode([
                'name'     => $name,
                'command'  => $command,
                'last_run' => $now,
            ], JSON_PRETTY_PRINT));

            if ($debug === true) {
                Output::info("Launching schedule '{$name}' → {$command}");
            }

            $pid = self::launch($name, $command, $phpBin, $bin, $timeout, $cacheDir);

            if ($pid > 0) {
                $lockFile = $cacheDir . $name . '.' . $pid . '.lock';
                file_put_contents($lockFile, json_encode([
                    'name'       => $name,
                    'command'    => $command,
                    'pid'        => $pid,
                    'started_at' => $now,
                    'timeout'    => $timeout,
                ], JSON_PRETTY_PRINT));

                if ($debug === true) {
                    Output::success("Schedule '{$name}' running (pid: {$pid}).");
                }
            } elseif ($debug === true) {
                Output::error("Schedule '{$name}' failed to launch (could not obtain PID).");
            }
        }
    }

    /**
     * Launches the schedule command as a background process.
     * Stderr is redirected to .cache/.flames/schedules/{name}.log for debugging.
     * Returns the PID, or 0 on failure.
     */
    protected static function launch(
        string $name,
        string $command,
        string $phpBin,
        string $bin,
        int|null $timeout,
        string $cacheDir
    ): int {
        $logFile = $cacheDir . $name . '.log';
        // Use a relative "forge" path so that $_SERVER['SCRIPT_FILENAME']
        // equals 'forge', which Cli::isCli() requires.
        $phpCmd = escapeshellcmd($phpBin) . ' forge ' . escapeshellarg($command);

        if ($timeout !== null && Os::isUnix() === true) {
            $phpCmd = 'timeout ' . (int)$timeout . ' ' . $phpCmd;
        }

        // cd first, then run — timeout wraps only the php invocation, not cd.
        $cmd = 'cd ' . escapeshellarg(ROOT_PATH) . ' && ' . $phpCmd;

        if (Os::isUnix() === true) {
            $fullCmd = $cmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';
            exec($fullCmd, $output);
            $pid = (int)($output[0] ?? 0);

            // Brief pause to let the process register in /proc
            if ($pid > 0) {
                usleep(50_000);
            }

            // Double-check via /proc to confirm the PID is real
            if ($pid > 0 && Os::isLinux() === true && file_exists('/proc/' . $pid) === false) {
                return 0;
            }

            return $pid;
        }

        // Windows fallback
        if ($procSocket = proc_open('start /b ' . $cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
        ], $pipes)) {
            $status = proc_get_status($procSocket);
            return (int)($status['pid'] ?? 0);
        }

        return 0;
    }

    protected static function hasRunningLocks(string $cacheDir, string $name): bool
    {
        $now     = time();
        $pattern = $cacheDir . $name . '.*.lock';
        foreach (glob($pattern) ?: [] as $lockFile) {
            $data      = json_decode(file_get_contents($lockFile), true);
            $pid       = (int)($data['pid']        ?? 0);
            $startedAt = (int)($data['started_at'] ?? 0);
            if ($pid > 0 && Process::isRunning($pid) === true) {
                return true;
            }
            // Clean up only locks older than 30 s so Show can display "completed"
            if (($now - $startedAt) >= 30) {
                @unlink($lockFile);
            }
        }
        return false;
    }

    protected static function hasSubMinuteSchedules(array $schedules): bool
    {
        foreach ($schedules as $schedule) {
            $every = $schedule['run']['every'] ?? [];
            if (empty($every['second']) === false) {
                return true;
            }
        }
        return false;
    }

    public static function calculateInterval(array $every): int
    {
        $seconds = 0;
        if (!empty($every['second'])) $seconds += (int)$every['second'];
        if (!empty($every['minute'])) $seconds += (int)$every['minute'] * 60;
        if (!empty($every['hour']))   $seconds += (int)$every['hour']   * 3600;
        if (!empty($every['day']))    $seconds += (int)$every['day']    * 86400;
        if (!empty($every['month']))  $seconds += (int)$every['month']  * 2592000;
        return $seconds;
    }
}
