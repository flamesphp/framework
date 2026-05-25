<?php

namespace Flames\Cli\Command;

use Flames\Command;
use Flames\Environment;

final class Server
{
    protected bool $debug = false;

    protected string $host = '0.0.0.0';
    protected int $port = 80;

    /**
     * Constructor for the class.
     *
     * @param object $data The data object containing the options.
     * @return void
     */
    public function __construct($data)
    {
        if ($data->argument->count > 0) {
            $uri = explode(':', $data->argument[0]);
            if (count($uri) === 2) {
                $this->host = $uri[0];
                $this->port = $uri[1];
            }
        }

        if (isset($data->parameter->host) === true) {
            $this->host = $data->parameter->host;
        }
        if (isset($data->parameter->port) === true) {
            $this->port = $data->parameter->port;
        }

        if ($this->host === '') {
            $this->host = '0.0.0.0';
        }
        if ($this->port <= 0) {
            $this->port = 80;
        }
    }

    /**
     * Executes the run method.
     *
     * @param bool $debug (optional) Determines if debugging is enabled. Default is false.
     *
     * @return bool Indicates the success or failure of the run method.
     */
    public function run(bool $debug = false) : bool
    {
        return \Flames\PHP\Server::run($this->host, $this->port);
    }
}