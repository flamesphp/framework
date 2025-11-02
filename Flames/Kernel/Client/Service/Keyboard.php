<?php

namespace Flames\Kernel\Client\Service;

use Flames\Collection\Strings;
use Flames\Element;
use Flames\Event\Element\KeyDown;
use Flames\Event\Element\KeyUp;
use Flames\Js;
use Flames\Kernel\Client\Error;

/**
 * @internal
 */
class Keyboard
{
    protected static array $keys = [];
    protected static array $keyCodes = [];

    protected static array $keyDownDelegates = [];
    protected static array $keyUpDelegates = [];

    protected static ?bool $capsLockActive = null;

    public static function register(): void
    {
        $element = Element::getBody();
        if ($element === null) {
            return;
        }

        $element->element->addEventListener('keydown', function ($event) use ($element) {
            try {
                if ($event->getModifierState !== null) {
                    $capsLockActive = $event->getModifierState('CapsLock');
                    if ($capsLockActive !== null) {
                        self::$capsLockActive = $capsLockActive;
                    }
                }
            } catch (\Exception|\Error $e) {}

            try {
                $keyLower = mb_strtolower($event->key, 'UTF-8');
            } catch (\Exception|\Error $e) { $keyLower = strtolower($event->key); }

            if ($keyLower !== '' && $keyLower !== null) {
                self::$keys[$keyLower] = true;
            }
            if ($event->keyCode !== '' && $event->keyCode !== null) {
                self::$keyCodes[$event->keyCode] = true;
            }
            $preventDefault = false;

            if (count(self::$keyDownDelegates) > 0) {
                $keyDownEvent = new KeyDown(Element::fromNative($element), $event);
                foreach (self::$keyDownDelegates as $delegate) {
                    try {
                        $return = $delegate($keyDownEvent);
                        if ($return === false) {
                            $preventDefault = true;
                        }
                    } catch (\Exception|\Error $e) {
                        Error::handler($e);
                        return null;
                    }
                }

                if ($preventDefault === true) {
                    $event->preventDefault();
                    return false;
                }
            }

            return null;
        });

        $element->element->addEventListener('keyup', function ($event) use ($element) {
            try {
                if ($event->getModifierState !== null) {
                    $capsLockActive = $event->getModifierState('CapsLock');
                    if ($capsLockActive !== null) {
                        self::$capsLockActive = $capsLockActive;
                    }
                }
            } catch (\Exception|\Error $e) {}

            try {
                $keyLower = mb_strtolower($event->key, 'UTF-8');
            } catch (\Exception|\Error $e) { $keyLower = strtolower($event->key); }

            if ($keyLower !== '' && $keyLower !== null) {
                unset(self::$keys[$keyLower]);
            }
            if ($event->keyCode !== '' && $event->keyCode !== null) {
                unset(self::$keyCodes[$event->keyCode]);
            }
            $preventDefault = false;

            if (count(self::$keyUpDelegates) > 0) {
                $keyUpEvent = new KeyUp(Element::fromNative($element), $event);
                foreach (self::$keyUpDelegates as $delegate) {
                    try {
                        $return = $delegate($keyUpEvent);
                        if ($return === false) {
                            $preventDefault = true;
                        }
                    } catch (\Exception|\Error $e) {
                        Error::handler($e);
                        return null;
                    }
                }

                if ($preventDefault === true) {
                    $event->preventDefault();
                    return false;
                }
            }

            return null;
        });
    }

    public static function isKeyPressed(string $key): bool
    {
        try {
            $keyLower = mb_strtolower($key, 'UTF-8');
        } catch (\Exception|\Error $e) { $keyLower = strtolower($key); }

        return isset(self::$keys[$keyLower]);
    }

    public static function isKeyCodePressed(int $keyCode): bool
    {
        return isset(self::$keyCodes[$keyCode]);
    }

    public static function registerKeyDown(\Closure $delegate): void
    {
        self::$keyDownDelegates[] = $delegate;
    }

    public static function registerKeyUp(\Closure $delegate): void
    {
        self::$keyUpDelegates[] = $delegate;
    }

    public static function isCapsLockActive(): bool
    {
        return self::$capsLockActive;
    }
}