<?php

namespace Flames\Event\Clipboard;

use Flames\Js;

/**
 * Description for the class
 *
 * @property ?string $data
 */
class Paste
{
    protected ?string $data = null;

    public function __construct($event)
    {
        $this->getData($event);
    }

    protected function getData($event)
    {
        if ($event->clipboardData !== null) {
            try {
                $this->data = $event->clipboardData->getData('text/plain');
            } catch (\Exception|\Error $e) {}
        } else {
            $window = Js::getWindow();
            if ($window->clipboardData !== null) {
                try {
                    $this->data = $event->clipboardData->getData('text/plain');
                } catch (\Exception|\Error $e) {}
            }
        }
    }

    public function __get(string $key) : mixed
    {
        $key = strtolower((string)$key);

        if ($key === 'data') {
            return $this->data;
        }

        return null;
    }
}