<?php
declare(strict_types=1);


namespace Flames;

use Flames\Forge\Cli\Data;
use Flames\Forge\Cli\System;

/**
 * The Command class provides a static method to run a command.
 */
class Command
{
    /**
     * Runs the specified command.
     *
     * @param string $command The command to be executed.
     * @param bool $debug [Optional] Set to true to enable debug mode, false by default.
     *
     * @return bool Returns true if the command was executed successfully, false otherwise.
     */
    public static function run(string $command, bool $debug = false) : bool
    {
        $args = ['forge'];

        $_args = explode(' ', $command);
        foreach ($_args as $arg) {
            $args[] = $arg;
        }
        $system = new System(Data::getData($args), $debug);
        return $system->run();
    }
}