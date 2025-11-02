<?php

namespace Flames\Client\Browser;

use Flames\Client\Platform;
use Flames\Js;
use Flames\Kernel;
use Flames\Kernel\Client\Dispatch\Native;

class DevTools
{
    protected static bool $isVirtualDevToolSetup = false;

    public static function open(bool $force = false): void
    {
        Native::add('devtools-open');

        if (self::isNativeBuild() === false && (Platform::isMobile() === true || $force === true)) {

            $window = Js::getWindow();
            $window->Flames->Internal->DevTools->open();

            if ($window->localStorage !== null) {
                $window->localStorage->setItem('flames-internal-devtools-open', 1);
            }

            self::$isVirtualDevToolSetup = true;
        }
    }

    public static function close(): void
    {
        Native::add('devtools-close');

        if (self::isNativeBuild() === false) {
            if (self::$isVirtualDevToolSetup === false) {
                return;
            }

            $window = Js::getWindow();
            $window->Flames->Internal->DevTools->close();

            if ($window->localStorage !== null) {
                $window->localStorage->setItem('flames-internal-devtools-open', 0);
            }
        }
    }

    protected static function isNativeBuild(): bool
    { return Kernel::isNativeBuild(); }
}