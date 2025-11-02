<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\Node\Expression;

use Flames\Template\Compiler;
use Flames\Template\Node\Node;

/**
 * @internal
 */
class ArrowFunctionExpression extends AbstractExpression
{
    public function __construct(AbstractExpression $expr, Node $names, $lineno, $tag = null)
    {
        parent::__construct(['expr' => $expr, 'names' => $names], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->raw('function (')
        ;
        foreach ($this->getNode('names') as $i => $name) {
            if ($i) {
                $compiler->raw(', ');
            }

            $compiler
                ->raw('$__')
                ->raw($name->getAttribute('name'))
                ->raw('__')
            ;
        }
        $compiler
            ->raw(') use ($context, $macros) { ')
        ;
        foreach ($this->getNode('names') as $name) {
            $compiler
                ->raw('$context["')
                ->raw($name->getAttribute('name'))
                ->raw('"] = $__')
                ->raw($name->getAttribute('name'))
                ->raw('__; ')
            ;
        }
        $compiler
            ->raw('return ')
            ->subcompile($this->getNode('expr'))
            ->raw('; }')
        ;
    }
}
