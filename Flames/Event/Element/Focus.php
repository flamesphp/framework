<?php

namespace Flames\Event\Element;

use Flames\Element;

/**
 * Description for the class
 * @property Element|null $target
 */
class Focus
{
    protected Element|null $target = null;

    protected bool $focus = false;

    public function __construct(Element $target, bool $focus = false)
    {
        $this->target = $target;
        $this->focus = $focus;
    }

    public function __get(string $key) : mixed
    {
        $key = strtolower((string)$key);

        if ($key === 'target') {
            return $this->target;
        }
        if ($key === 'focus') {
            return $this->focus;
        }

        return null;
    }
}