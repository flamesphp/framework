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
use Flames\Template\Node\Expression\AbstractExpression;

/**
 * @internal
 */
#[YieldReady]
class CheckToStringNode extends AbstractExpression
{
    public function __construct(AbstractExpression $expr)
    {
        parent::__construct(['expr' => $expr], [], $expr->getTemplateLine(), $expr->getNodeTag());
    }

    public function compile(Compiler $compiler): void
    {
        $expr = $this->getNode('expr');
        $compiler
            ->raw('$this->sandbox->ensureToStringAllowed(')
            ->subcompile($expr)
            ->raw(', ')
            ->repr($expr->getTemplateLine())
            ->raw(', $this->source)')
        ;
    }
}
