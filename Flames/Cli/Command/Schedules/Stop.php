<?php

namespace Flames\Cli\Command\Schedules;

use Flames\Cli\Output;
use Flames\Server\Process;

/**
 * @internal
 */
final class Stop
{
    protected string|null $target;

    /**
     * Accepts either a schedule name or a PID.
     * Usage: php bin schedules:stop {name|pid}
     */
    public function __construct($data)
    {
        $this->target = $data->argument[0] ?? null;
    }

    public function run(bool $debug = false): bool
    {
        if (empty($this->target) === true) {
            Output::error('Usage: schedules:stop {name|pid}');
            return false;
        }

        $cacheDir = ROOT_PATH . '.cache/.flames/schedules/';
        $locks    = glob($cacheDir . '*.*.lock');

        if (empty($locks) === true) {
            Output::warning('No running schedules found.');
            return true;
        }

        // Collect all running processes, assigning sequential IDs
        $running = [];
        $seq     = 1;
        foreach ($locks as $lockFile) {
            $data = json_decode(file_get_contents($lockFile), true);
            if ($data === null) {
                continue;
            }
            $pid = (int)($data['pid'] ?? 0);
            if ($pid <= 0 || Process::isRunning($pid) === false) {
                @unlink($lockFile);
                continue;
            }
            $running[] = [
                'id'       => $seq++,
                'name'     => $data['name']    ?? '',
                'pid'      => $pid,
                'lockFile' => $lockFile,
            ];
        }

        if (empty($running) === true) {
            Output::warning('No running schedules found.');
            return true;
        }

        $killed = 0;
        foreach ($running as $entry) {
            $match = (string)$entry['id']   === $this->target
                  || (string)$entry['pid']  === $this->target
                  || $entry['name']         === $this->target;

            if ($match === false) {
                continue;
            }

            $process = Process::getCurrent();
            // Use destroy() via a fresh Process instance carrying the PID
            self::killPid($entry['pid']);
            @unlink($entry['lockFile']);

            Output::success("Stopped schedule '{$entry['name']}' (pid: {$entry['pid']}).");
            $killed++;
        }

        if ($killed === 0) {
            Output::warning("No running schedule matched '{$this->target}'.");
            return false;
        }

        Output::blank();
        return true;
    }

    protected static function killPid(int $pid): void
    {
        // Reuse Process::destroy() logic without spawning a new command
        if (\Flames\Server\Os::isUnix() === true) {
            exec('kill -9 ' . $pid);
            return;
        }
        exec('taskkill /pid ' . $pid . ' /F');
    }
}
