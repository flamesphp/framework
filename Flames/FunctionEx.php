<?php

namespace Flames;

use Closure;
use Flames\Kernel\Client\Error;

class FunctionEx
{
    public static function delay(Closure $delegate, int $delayMs)
    {
        if (Kernel::MODULE === 'CLIENT') {
            $window = Js::getWindow();
            $window->setTimeout(function () use ($delegate) {
                try {
                    $delegate();
                } catch (\Exception|\Error $e) {
                    Error::handler($e);
                }
            }, $delayMs);
            return;
        }
    }
}