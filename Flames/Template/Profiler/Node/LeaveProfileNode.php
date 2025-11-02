<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\Profiler\Node;

use Flames\Template\Attribute\YieldReady;
use Flames\Template\Compiler;
use Flames\Template\Node\Node;

/**
 * @internal
 */
#[YieldReady]
class LeaveProfileNode extends Node
{
    public function __construct(string $varName)
    {
        parent::__construct([], ['var_name' => $varName]);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->write("\n")
            ->write(sprintf("\$%s->leave(\$%s);\n\n", $this->getAttribute('var_name'), $this->getAttribute('var_name').'_prof'))
        ;
    }
}
