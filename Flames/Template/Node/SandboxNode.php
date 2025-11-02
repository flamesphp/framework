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
class SandboxNode extends Node
{
    public function __construct(Node $body, int $lineno, ?string $tag = null)
    {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("if (!\$alreadySandboxed = \$this->sandbox->isSandboxed()) {\n")
            ->indent()
            ->write("\$this->sandbox->enableSandbox();\n")
            ->outdent()
            ->write("}\n")
            ->write("try {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("} finally {\n")
            ->indent()
            ->write("if (!\$alreadySandboxed) {\n")
            ->indent()
            ->write("\$this->sandbox->disableSandbox();\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n")
        ;
    }
}
