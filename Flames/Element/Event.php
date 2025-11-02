<?php

namespace Flames\Element;

use Flames\Collection\Strings;
use Flames\Element;
use Flames\Event\Element\Click;
use Flames\Event\Element\Change;
use Flames\Event\Element\Focus;
use Flames\Event\Element\Input;
use Flames\Event\Element\KeyDown;
use Flames\Event\Element\KeyUp;
use Flames\Event\Element\KeyPress;
use Flames\Js;
use Flames\Kernel\Client\Error;

/**
 * @internal
 */
class Event
{
    private $element = null;

    public function __construct($element)
    {
        $this->element = $element;
    }

    public function click(\Closure $delegate)
    {
        $tag = Strings::toLower($this->element->tagName);
        $element = $this->element;

        $this->element->addEventListener('click', function ($event) use ($delegate, $tag, $element) {
            if ($tag === 'a') {
                $event->preventDefault();
            }

            try {
                $delegate(new Click(Element::fromNative($element)));
            } catch (\Exception|\Error $e) {
                Error::handler($e);
                return;
            }
        });
    }

    public function change(\Closure $delegate)
    {
        $element = $this->element;
        $this->element->addEventListener('change', function ($event) use ($delegate, $element) {
            try {
                $delegate(new Change(Element::fromNative($element)));
            } catch (\Exception|\Error $e) {
                Error::handler($e);
                return;
            }
        });
    }

    public function input(\Closure $delegate)
    {
        $element = $this->element;
        $this->element->addEventListener('input', function ($event) use ($delegate, $element) {
            try {
                $delegate(new Input(Element::fromNative($element)));
            } catch (\Exception|\Error $e) {
                Error::handler($e);
                return;
            }
        });
    }

    public function keyDown(\Closure $delegate)
    {
        $element = $this->element;
        $this->element->addEventListener('keydown', function ($event) use ($delegate, $element) {
            try {
                $delegate(new KeyDown(Element::fromNative($element), $event));
            } catch (\Exception|\Error $e) {
                Error::handler($e);
                return;
            }
        });
    }

    public function keyUp(\Closure $delegate)
    {
        $element = $this->element;
        $this->element->addEventListener('keyup', function ($event) use ($delegate, $element) {
            try {
                $delegate(new KeyUp(Element::fromNative($element), $event));
            } catch (\Exception|\Error $e) {
                Error::handler($e);
                return;
            }
        });
    }

    public function focus(\Closure $delegate)
    {
        $element = $this->element;
        $this->element->addEventListener('focus', function ($event) use ($delegate, $element) {
            try {
                $delegate(new Focus(Element::fromNative($element), true));
            } catch (\Exception|\Error $e) {
                Error::handler($e);
                return;
            }
        });
        $this->element->addEventListener('blur', function ($event) use ($delegate, $element) {
            try {
                $delegate(new Focus(Element::fromNative($element), false));
            } catch (\Exception|\Error $e) {
                Error::handler($e);
                return;
            }
        });
    }
}