<?php

namespace Flames\Event\Element;

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
class KeyUp
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
        $this->ctrlKeyPressed = $event->ctrlKey;
        $this->altKeyPressed = $event->altKey;
        $this->shiftKeyPressed = $event->shiftKey;
        $this->keyCode = $event->keyCode;

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

    public function isKeyUp(string $key): bool
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

        return ($keyLower === $_keyLower);
    }

    public function isKeyCodeUp(int $keyCode): bool
    {
        return ($this->keyCode === $keyCode);
    }
}