<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
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
class BlockReferenceNode extends Node implements NodeOutputInterface
{
    public function __construct(string $name, int $lineno, ?string $tag = null)
    {
        parent::__construct([], ['name' => $name], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf("yield from \$this->unwrap()->yieldBlock('%s', \$context, \$blocks);\n", $this->getAttribute('name')))
        ;
    }
}
