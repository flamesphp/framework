<?php

namespace Flames;

/**
 * @deprecated Use \Flames\Forge\Cli instead.
 * @internal
 */
final class Cli
{
    public static function isCli(): bool
    {
        return \Flames\Forge\Cli::isCli();
    }
}
