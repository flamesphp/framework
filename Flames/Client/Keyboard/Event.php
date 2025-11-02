<?php

namespace Flames\Client\Keyboard;

use Flames\Kernel\Client\Service\Keyboard as KeyboardService;

class Event
{
    public static function keyDown(\Closure $delegate): void
    {
        KeyboardService::registerKeyDown($delegate);
    }

    public static function keyUp(\Closure $delegate): void
    {
        KeyboardService::registerKeyUp($delegate);
    }
}