<?php

namespace Flames\Event\Element;

use Flames\Client\Keyboard;
use Flames\Element;

/**
 * Description for the class
 *
 * @property Element|null $target
 * @property string|bool|null $value
 * @property bool $ctrlKeyPressed
 * @property bool $altKeyPressed
 * @property bool $shiftKeyPressed
 * @property int $keyCode
 * @property string $key
 */
class KeyDown
{
    protected Element|null $target = null;

    protected bool $ctrlKeyPressed = false;
    protected bool $altKeyPressed = false;
    protected bool $shiftKeyPressed = false;
    protected ?int $keyCode = null;
    protected ?string $key = null;

    public function __construct(Element $target, $event)
    {
        $this->target = $target;
        $this->ctrlKeyPressed = (bool)$event->ctrlKey;
        $this->altKeyPressed = (bool)$event->altKey;
        $this->shiftKeyPressed = (bool)$event->shiftKey;
        $this->keyCode = (string)$event->keyCode;

        try {
            $keyLower = mb_strtolower($event->key);
        } catch (\Exception|\Error $e) {
            $keyLower = strtolower($event->key);
        }

        $this->key = $keyLower;
    }

    public function __get(string $key) : mixed
    {
        $key = strtolower((string)$key);

        if ($key === 'target') {
            return $this->target;
        }
        elseif ($key === 'value') {
            return $this->target->value;
        }
        elseif ($key === 'ctrlkeypressed') {
            return $this->ctrlKeyPressed;
        }
        elseif ($key === 'altkeypressed') {
            return $this->altKeyPressed;
        }
        elseif ($key === 'shiftkeypressed') {
            return $this->shiftKeyPressed;
        }
        elseif ($key === 'keycode') {
            return $this->keyCode;
        }
        elseif ($key === 'key') {
            return $this->key;
        }

        return null;
    }

    public function isKeyDown(string $key, bool $withCtrl = false, bool $withAlt = false, bool $withShift = false): bool
    {
        try {
            $_keyLower = mb_strtolower($key);
        } catch (\Exception|\Error $e) {
            $_keyLower = strtolower($key);
        }

        try {
            $keyLower = mb_strtolower($this->key);
        } catch (\Exception|\Error $e) {
            $keyLower = strtolower($this->key);
        }

        $down = ($keyLower === $_keyLower);
        if ($down === false) {
            return false;
        }

        if ($withCtrl === true && $this->ctrlKeyPressed === false) {
            return false;
        }
        if ($withAlt === true && $this->altKeyPressed === false) {
            return false;
        }
        if ($withShift === true && $this->shiftKeyPressed === false) {
            return false;
        }

        return true;
    }

    public function isKeyCodeDown(int $keyCode, bool $withCtrl = false, bool $withAlt = false, bool $withShift = false): bool
    {
        $down = ($this->keyCode === $keyCode);
        if ($down === false) {
            return false;
        }

        if ($withCtrl === true && $this->ctrlKeyPressed === false) {
            return false;
        }
        if ($withAlt === true && $this->altKeyPressed === false) {
            return false;
        }
        if ($withShift === true && $this->shiftKeyPressed === false) {
            return false;
        }

        return true;
    }

    public function isCombination(string $combination):bool
    {
        try {
            $combination = mb_strtolower($combination);
        } catch (\Exception|\Error $e) {
            $combination = strtolower($combination);
        }

        $split = explode('+', $combination);
        if (count($split) === 1) {
            return false;
        }

        foreach ($split as $key) {
            if ($key === 'ctrl') {
                $key = 'control';
            }
            if (Keyboard::isKeyPressed($key) === false) {
                return false;
            }
        }

        return true;
    }
}