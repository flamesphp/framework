<?php
declare(strict_types=1);


namespace Flames\Event;

abstract class Load
{
    public function onLoad(string $name) : bool
    {
        return false;
    }
}