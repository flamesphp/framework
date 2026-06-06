<?php
declare(strict_types=1);


namespace Flames\Server;

use Flames\Server\Os;

/**
 * Class Process
 *
 * The Process class represents a running process on the system.
 */
class Process
{
    protected int $pid;

    /**
     * Constructor method for the class.
     *
     * @param string $command The command to be executed.
     */
    public function __construct(?string $command = null)
    {
        if ($command === null) {
            return;
        }

        if (Os::isUnix() === true) {
            $command = ($command . ' > /dev/null 2>&1 & echo $!');
            exec($command, $output);

            if (is_array($output) === true) {
                $this->pid = (int)$output[0];
            }

            return;
        }

        if ($procSocket = proc_open("start /b " . $command, [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
        ], $pipes)) {
            $procStatus = proc_get_status($procSocket);
            $this->pid = $procStatus['pid'];
        }
    }

    /**
     * Get the process ID.
     *
     * @return int|null The process ID if available, otherwise null.
     */
    public function getPid() : int|null
    {
        return $this->pid;
    }

    /**
     * Method to destroy the running process.
     *
     * @return void
     */
    public function destroy() : void
    {
        if (Os::isUnix() === true) {
            exec('kill -9 ' . $this->pid);
            return;
        }

        exec('taskkill /pid ' . $this->pid . ' /F');
    }

    public static function getCurrent()
    {
        $process = new self();
        $process->pid = getmypid();
        return $process;
    }

    /**
     * Checks whether a process with the given PID is still running.
     *
     * @param int $pid
     * @return bool
     */
    public static function isRunning(int $pid): bool
    {
        if (Os::isLinux() === true) {
            // /proc/{pid}/status is the most reliable check on Linux;
            // it works regardless of process ownership and avoids WSL quirks.
            $statusFile = '/proc/' . $pid . '/status';
            if (file_exists($statusFile) === false) {
                return false;
            }
            $status = @file_get_contents($statusFile);
            // Zombie processes are effectively dead for our purposes
            if ($status !== false && preg_match('/^State:\s+Z/m', $status)) {
                return false;
            }
            return true;
        }

        if (Os::isUnix() === true) {
            return posix_kill($pid, 0) === true;
        }

        exec('tasklist /FI "PID eq ' . $pid . '" 2>NUL', $output);
        foreach ($output as $line) {
            if (str_contains($line, (string)$pid)) {
                return true;
            }
        }
        return false;
    }
}