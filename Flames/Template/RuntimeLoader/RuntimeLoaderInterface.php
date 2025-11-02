<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\RuntimeLoader;

/**
 * @internal
 */
interface RuntimeLoaderInterface
{
    /**
     * Creates the runtime implementation of a Template element (filter/function/test).
     *
     * @return object|null The runtime instance or null if the loader does not know how to create the runtime for this class
     */
    public function load(string $class);
}
