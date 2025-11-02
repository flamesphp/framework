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
class CaptureNode extends Node
{
    public function __construct(Node $body, int $lineno, ?string $tag = null)
    {
        parent::__construct(['body' => $body], ['raw' => false, 'with_blocks' => false], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $useYield = $compiler->getEnvironment()->useYield();

        if (!$this->getAttribute('raw')) {
            $compiler->raw("('' === \$tmp = ");
        }
        $compiler->raw($useYield ? "implode('', iterator_to_array(" : "\\Template\\Extension\\CoreExtension::captureOutput(");
        if ($this->getAttribute('with_blocks')) {
            $compiler->raw("(function () use (&\$context, \$macros, \$blocks) {\n");
        } else {
            $compiler->raw("(function () use (&\$context, \$macros) {\n");
        }
        $compiler
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("})() ?? new \EmptyIterator())")
        ;
        if ($useYield) {
            $compiler->raw(')');
        }
        if (!$this->getAttribute('raw')) {
            $compiler->raw(") ? '' : new Markup(\$tmp, \$this->env->getCharset())");
        }
        $compiler->raw(';');
    }
}
