<?php

namespace Flames\Server;

use Flames\Server\Os;

/**
 * Class Process
 *
 * The Process class represents a running process on the system.
 */
class Shell
{
    protected ?int $code = null;
    protected ?string $output = null;

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

        exec($command, $output, $returnCode);
        $this->code = (int)$returnCode;

        if (is_array($output) === true) {
            $this->output = implode("\n", $output);
            return;
        }

        $this->output = (string)$output;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }
}