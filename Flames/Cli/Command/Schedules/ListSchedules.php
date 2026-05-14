<?php

namespace Flames\Cli\Command\Schedules;

use Flames\Cli\Output;
use Flames\Kernel\Config;

/**
 * @internal
 */
final class ListSchedules
{
    public function __construct($data) {}

    public function run(bool $debug = false): bool
    {
        $config    = Config::get();
        $schedules = $config['schedules'] ?? [];

        Output::section('Configured Schedules');

        if (empty($schedules) === true) {
            Output::warning('No schedules defined in config.yml.');
            return true;
        }

        $col = [18, 18, 16, 10, 11];
        self::row(['NAME', 'COMMAND', 'EVERY', 'TIMEOUT', 'OVERLAPPING'], $col, true);
        self::divider($col);

        foreach ($schedules as $name => $schedule) {
            $command     = $schedule['command']            ?? '—';
            $every       = $schedule['run']['every']       ?? [];
            $timeout     = $schedule['timeout']['seconds'] ?? '—';
            $overlapping = $schedule['overlapping']        ?? true;

            self::row([
                $name,
                $command,
                self::formatEvery($every),
                $timeout === '—' ? '—' : $timeout . 's',
                $overlapping ? 'true' : 'false',
            ], $col);
        }

        Output::blank();
        return true;
    }

    protected static function formatEvery(array $every): string
    {
        $parts = [];
        if (!empty($every['second'])) $parts[] = $every['second'] . 's';
        if (!empty($every['minute'])) $parts[] = $every['minute'] . 'm';
        if (!empty($every['hour']))   $parts[] = $every['hour']   . 'h';
        if (!empty($every['day']))    $parts[] = $every['day']    . 'd';
        if (!empty($every['month']))  $parts[] = $every['month']  . 'mo';
        return empty($parts) ? '—' : 'every ' . implode(' ', $parts);
    }

    protected static function row(array $cells, array $widths, bool $header = false): void
    {
        $color = $header ? Output::YELLOW . Output::BOLD : Output::WHITE;
        $line  = '  ';
        foreach ($cells as $i => $cell) {
            $w    = $widths[$i] ?? 14;
            $cell = substr((string)$cell, 0, $w);
            $line .= $color . str_pad($cell, $w) . Output::RESET . '  ';
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
