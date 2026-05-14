<?php

namespace Flames\Cli\Command\Schedules;

use Flames\Cli\Output;
use Flames\Server\Os;

/**
 * @internal
 *
 * Removes the "forge schedule run" crontab entry created by `schedule install`.
 */
final class Remove
{
    public function __construct($data) {}

    public function run(bool $debug = false): bool
    {
        if (Os::isUnix() === false) {
            Output::warning('Crontab management is only supported on Unix/Linux/macOS.');
            return false;
        }

        $marker      = 'forge schedule run';
        $currentCron = $this->readCrontab();

        if (!str_contains($currentCron, $marker)) {
            Output::info('forge schedule run is not registered in crontab. Nothing to remove.');
            return true;
        }

        $lines   = explode("\n", $currentCron);
        $kept    = [];
        $removed = 0;

        foreach ($lines as $line) {
            if (str_contains($line, $marker)) {
                $removed++;
            } else {
                $kept[] = $line;
            }
        }

        $newCron = implode("\n", $kept);
        // Trim trailing blank lines but keep a final newline
        $newCron = rtrim($newCron) . "\n";

        if ($this->writeCrontab($newCron) === false) {
            Output::error('Failed to write crontab. Try running: crontab -e');
            return false;
        }

        Output::success("Removed {$removed} crontab " . ($removed === 1 ? 'entry' : 'entries') . " for forge schedule run.");
        return true;
    }

    protected function readCrontab(): string
    {
        $output = shell_exec('crontab -l 2>/dev/null');
        return $output !== null ? $output : '';
    }

    protected function writeCrontab(string $content): bool
    {
        // An empty crontab should remove all entries
        if (trim($content) === '') {
            shell_exec('crontab -r 2>/dev/null');
            return true;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'forge_cron_');
        if ($tmp === false) {
            return false;
        }

        file_put_contents($tmp, $content);
        $result = shell_exec('crontab ' . escapeshellarg($tmp) . ' 2>&1');
        unlink($tmp);

        return $result === null || trim($result) === '';
    }
}
