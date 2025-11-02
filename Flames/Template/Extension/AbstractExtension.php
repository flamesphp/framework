<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\Extension;

/**
 * @internal
 */
abstract class AbstractExtension implements ExtensionInterface
{
    public function getTokenParsers()
    {
        return [];
    }

    public function getNodeVisitors()
    {
        return [];
    }

    public function getFilters()
    {
        return [];
    }

    public function getTests()
    {
        return [];
    }

    public function getFunctions()
    {
        return [];
    }

    public function getOperators()
    {
        return [];
    }
}
