<?php
declare(strict_types=1);


namespace Flames\Client\Event;

/**
 * Represents an attribute that signifies a input.
 *
 * This attribute can be used to mark methods
 * that have undergone changes and provide relevant information about the inputs made.
 *
 */
#[\Attribute(\Attribute::TARGET_CLASS, \Attribute::TARGET_METHOD)]
class Input
{
    /**
     * Represents an attribute that signifies a inputs.
     *
     * This attribute can be used to mark methods
     * that have undergone changes and provide relevant information about the inputs made.
     *
     * @param string $uid string file path.
     */
    public function __construct(string $uid)
    {

    }
}
