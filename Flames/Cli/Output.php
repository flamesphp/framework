<?php

namespace Flames\Cli;

/** @deprecated Use \Flames\Forge\Cli\Output instead. @internal */
class Output
{
    public static function __callStatic(string $name, array $args): mixed
    {
        return \Flames\Forge\Cli\Output::$name(...$args);
    }
}
