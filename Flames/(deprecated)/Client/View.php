<?php
declare(strict_types=1);


namespace Flames\Client;

/**
 * Represents an attribute that signifies a View.
 *
 * This attribute can be used to mark methods
 * that have undergone changes and provide relevant information about the View.
 *
 */
#[\Attribute(\Attribute::TARGET_CLASS, \Attribute::TARGET_METHOD)]
class View
{
    /**
     * Represents an attribute that signifies a View.
     *
     * This attribute can be used to mark methods
     * that have undergone changes and provide relevant information about the View.
     *
     * @param string $path string file path.
     */
    public function __construct(string $path = null)
    {

    }
}