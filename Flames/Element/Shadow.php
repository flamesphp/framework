<?php

namespace Flames\Element;

use Flames\Element;
use Flames\Js;

/**
 * @internal
 */
class Shadow
{
    protected $shadow = null;

    public function __construct($shadow)
    {
        $this->shadow = $shadow;
    }

    public function subquery(string $query): ?Element
    {
        if ($this->shadow === null) {
            return null;
        }

        $element = $this->shadow->querySelector($query);
        if ($element === null) {
            return null;
        }

        return Element::fromNative($element);
    }
}
