<?php
declare(strict_types=1);


namespace Flames\Client\Event;

/**
 * Represents an attribute that signifies a click.
 *
 * This attribute can be used to mark methods
 * that have undergone changes and provide relevant information about the clicks made.
 *
 */
#[\Attribute(\Attribute::TARGET_CLASS, \Attribute::TARGET_METHOD)]
class Click
{
    /**
     * Represents an attribute that signifies a click.
     *
     * This attribute can be used to mark methods
     * that have undergone changes and provide relevant information about the clicks made.
     *
     * @param string $uid string file path.
     */
    public function __construct(string $uid)
    {

    }
}
