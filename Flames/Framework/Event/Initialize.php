<?php
declare(strict_types=1);


namespace Flames\Event;

abstract class Initialize
{
    public function onInitialize() : bool
    {
        return true;
    }
}