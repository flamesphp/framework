<?php
declare(strict_types=1);


namespace Flames\Event\Element;

use Flames\Element;

/**
 * Description for the class
 *
 * @property Element|null $target
 * @property string|bool|null $value
 */
class Input
{
    public function __construct(protected Element $target)
    {
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

        return null;
    }
}