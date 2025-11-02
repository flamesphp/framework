<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\Node\Expression\Binary;

use Flames\Template\Compiler;

/**
 * @internal
 */
class HasEveryBinary extends AbstractBinary
{
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('CoreExtension::arrayEvery($this->env, ')
            ->subcompile($this->getNode('left'))
            ->raw(', ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }

    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('');
    }
}
