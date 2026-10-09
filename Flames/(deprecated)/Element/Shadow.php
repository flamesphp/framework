<?php
declare(strict_types=1);


namespace Flames\Element;

use Flames\Element;
use Flames\Js;

/**
 * @internal
 */
class Shadow
{
    public function __construct(protected $shadow)
    {
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
