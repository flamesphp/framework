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
class FactoryRuntimeLoader implements RuntimeLoaderInterface
{
    private $map;

    /**
     * @param array $map An array where keys are class names and values factory callables
     */
    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    public function load(string $class)
    {
        if (!isset($this->map[$class])) {
            return null;
        }

        $runtimeFactory = $this->map[$class];

        return $runtimeFactory();
    }
}
