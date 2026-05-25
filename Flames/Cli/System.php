<?php

namespace Flames\Cli;

use Flames\Collection\Arr;

/** @deprecated Use \Flames\Forge\Cli\System instead. @internal */
class System
{
    public function __construct(Arr $data = null, bool $debug = true)
    {
        $this->inner = new \Flames\Forge\Cli\System($data, $debug);
    }
    private \Flames\Forge\Cli\System $inner;
    public function run(): bool { return $this->inner->run(); }
}
