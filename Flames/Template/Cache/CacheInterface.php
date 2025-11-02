<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\Cache;

/**
 * @internal
 */
interface CacheInterface
{
    /**
     * Generates a cache key for the given template class name.
     */
    public function generateKey(string $name, string $className): string;

    /**
     * Writes the compiled template to cache.
     *
     * @param string $content The template representation as a PHP class
     */
    public function write(string $key, string $content): void;

    /**
     * Loads a template from the cache.
     */
    public function load(string $key): void;

    /**
     * Returns the modification timestamp of a key.
     */
    public function getTimestamp(string $key): int;
}
