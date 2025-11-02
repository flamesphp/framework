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
class TextNode extends Node implements NodeOutputInterface
{
    public function __construct(string $data, int $lineno)
    {
        parent::__construct([], ['data' => $data], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->write('yield ')
            ->string($this->getAttribute('data'))
            ->raw(";\n")
        ;
    }
}
