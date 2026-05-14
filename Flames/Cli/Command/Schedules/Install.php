<?php

namespace Flames\Cli\Command\Schedules;

use Flames\Cli\Output;
use Flames\Server\Os;

/**
 * @internal
 *
 * Registers "forge schedule run" in the system crontab so schedules are
 * triggered automatically every minute.
 *
 * The cron entry runs: cd {ROOT_PATH} && php forge schedule run
 * This lets the forge launcher handle native/Docker routing automatically,
 * exactly as if the developer ran the command manually.
 */
final class Install
{
    public function __construct($data) {}

    public function run(bool $debug = false): bool
    {
        if (Os::isUnix() === false) {
            Output::warning('Crontab setup is only supported on Unix/Linux/macOS.');
            Output::info('Add the following to Windows Task Scheduler:');
            echo '    ' . Output::CYAN
                . 'php ' . ROOT_PATH . 'forge schedule run'
                . Output::RESET . "\n";
            return false;
        }

        $php       = $this->findPhpBinary();
        $forgePath = ROOT_PATH . 'forge';
        $rootPath  = rtrim(ROOT_PATH, '/');
        $logPath   = ROOT_PATH . '.cache/.flames/schedules/cron.log';

        $cronCmd = "* * * * * cd {$rootPath} && {$php} forge schedule run >> {$logPath} 2>&1";
        $marker  = 'forge schedule run';

        // Load current crontab
        $currentCron = $this->readCrontab();

        // Check if already installed
        if (str_contains($currentCron, $marker)) {
            Output::info('forge schedule run is already registered in crontab.');
            Output::blank();
            $this->printCurrentEntry($currentCron, $marker);
            return true;
        }

        // Append new entry
        $newCron = rtrim($currentCron) . "\n" . $cronCmd . "\n";
        if ($this->writeCrontab($newCron) === false) {
            Output::error('Failed to write crontab. Try running: crontab -e');
            return false;
        }

        Output::success('Crontab entry added successfully.');
        Output::blank();
        echo '    ' . Output::CYAN . $cronCmd . Output::RESET . "\n";
        Output::blank();
        Output::info('Logs will be written to: ' . $logPath);

        return true;
    }

    protected function findPhpBinary(): string
    {
        $which = trim((string)shell_exec('which php 2>/dev/null'));
        return $which !== '' ? $which : 'php';
    }

    protected function readCrontab(): string
    {
        $output = shell_exec('crontab -l 2>/dev/null');
        return $output !== null ? $output : '';
    }

    protected function writeCrontab(string $content): bool
    {
        $tmp = tempnam(sys_get_temp_dir(), 'forge_cron_');
        if ($tmp === false) {
            return false;
        }

        file_put_contents($tmp, $content);
        $result = shell_exec('crontab ' . escapeshellarg($tmp) . ' 2>&1');
        unlink($tmp);

        return $result === null || trim($result) === '';
    }

    protected function printCurrentEntry(string $cron, string $marker): void
    {
        foreach (explode("\n", $cron) as $line) {
            if (str_contains($line, $marker)) {
                echo '    ' . Output::CYAN . trim($line) . Output::RESET . "\n";
            }
        }
        Output::blank();
    }
}
