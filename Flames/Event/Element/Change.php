<?php

namespace Flames\Event\Element;

use Flames\Element;

/**
 * Description for the class
 *
 * @property Element|null $target
 * @property string|bool|null $value
 * @property bool $checked
 */
class Change
{
    protected Element|null $target = null;

    public function __construct(Element $target)
    {
        $this->target = $target;
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
        elseif ($key === 'checked') {
            return $this->target->checked;
        }

        return null;
    }
}