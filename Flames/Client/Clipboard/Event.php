<?php

namespace Flames\Client\Clipboard;

use Flames\Kernel\Client\Service\Clipboard as ClipboardService;

class Event
{
    public static function paste(\Closure $delegate): void
    {
        ClipboardService::registerPaste($delegate);
    }
}