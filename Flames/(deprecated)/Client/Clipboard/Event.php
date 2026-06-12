<?php
declare(strict_types=1);


namespace Flames\Client\Clipboard;

use Flames\Kernel\Client\Service\Clipboard as ClipboardService;

class Event
{
    public static function paste(\Closure $delegate): void
    {
        ClipboardService::registerPaste($delegate);
    }
}