<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\Node;

use Flames\Template\Attribute\YieldReady;
use Flames\Template\Compiler;

/**
 * @internal
 */
#[YieldReady]
class FlushNode extends Node
{
    public function __construct(int $lineno, string $tag)
    {
        parent::__construct([], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("flush();\n")
        ;
    }
}
