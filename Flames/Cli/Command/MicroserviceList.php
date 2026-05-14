<?php

namespace Flames\Cli\Command;

use Flames\Cli\Output;
use Flames\Kernel\Config;

/**
 * @internal
 *
 * Lists all microservices defined in config.yml.
 *
 * Usage:
 *   forge microservice:list
 */
final class MicroserviceList
{
    public function __construct($data) {}

    public function run(bool $debug = false): bool
    {
        $config       = Config::get();
        $microservices = $config['microservices'] ?? [];

        echo "\n" . Output::YELLOW . Output::BOLD . "  MICROSERVICES" . Output::RESET . "\n\n";

        // Default App microservice is always present
        echo '  '
            . Output::GREEN . Output::BOLD . str_pad('App', 20)     . Output::RESET
            . Output::GRAY  . '(default)'                            . Output::RESET
            . "\n";

        if (empty($microservices)) {
            Output::blank();
            Output::info('No additional microservices configured in config.yml.');
            Output::blank();
            return true;
        }

        foreach ($microservices as $key => $entry) {
            $namespace = $entry['namespace'] ?? $key;
            $hosts     = $entry['hosts']     ?? [];

            $hostStr = empty($hosts)
                ? Output::GRAY . '(no hosts)' . Output::RESET
                : Output::CYAN . implode(Output::RESET . ', ' . Output::CYAN, $hosts) . Output::RESET;

            echo '  '
                . Output::GREEN . Output::BOLD . str_pad($namespace, 20) . Output::RESET
                . $hostStr
                . "\n";
        }

        echo "\n";
        return true;
    }
}
