<?php

namespace Flames\Client;

use Flames\Kernel\Client\Service\Keyboard as KeyboardService;

class Keyboard
{
    public static function isCtrlPressed(): bool
    {
        return KeyboardService::isKeyPressed('control');
    }

    public static function isShiftPressed(): bool
    {
        return KeyboardService::isKeyPressed('shift');
    }

    public static function isAltPressed(): bool
    {
        return KeyboardService::isKeyPressed('alt');
    }

    public static function isWindowsPressed(): bool
    {
        return self::isApplePressed();
    }

    public static function isApplePressed(): bool
    {
        return KeyboardService::isKeyPressed('meta');
    }

    public static function isKeyPressed(string $key): bool
    {
        return KeyboardService::isKeyPressed($key);
    }

    public static function isKeyCodePressed(int $keyCode): bool
    {
        return KeyboardService::isKeyCodePressed($keyCode);
    }

    public static function isCapsLockActive(): bool
    {
        return KeyboardService::isCapsLockActive();
    }
}