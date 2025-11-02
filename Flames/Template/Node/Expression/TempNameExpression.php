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

/**
 * @internal
 */
class TempNameExpression extends AbstractExpression
{
    public function __construct(string $name, int $lineno)
    {
        parent::__construct([], ['name' => $name], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('$_')
            ->raw($this->getAttribute('name'))
            ->raw('_')
        ;
    }
}
