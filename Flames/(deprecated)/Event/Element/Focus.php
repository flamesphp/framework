<?php
declare(strict_types=1);


namespace Flames\Event\Element;

use Flames\Element;

/**
 * Description for the class
 * @property Element|null $target
 */
class Focus
{
    public function __construct(protected Element $target, protected bool $focus = false)
    {
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