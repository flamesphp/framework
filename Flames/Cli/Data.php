<?php

namespace Flames\Cli;

/** @deprecated Use \Flames\Forge\Cli\Data instead. @internal */
class Data
{
    public static function getData(array $args = null): \Flames\Collection\Arr
    {
        return \Flames\Forge\Cli\Data::getData($args);
    }
}
