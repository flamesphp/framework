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
final class NullCache implements CacheInterface
{
    public function generateKey(string $name, string $className): string
    {
        return '';
    }

    public function write(string $key, string $content): void
    {
    }

    public function load(string $key): void
    {
    }

    public function getTimestamp(string $key): int
    {
        return 0;
    }
}
