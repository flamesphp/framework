<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\Node\Expression\Test;

use Flames\Template\Compiler;
use Flames\Template\Error\SyntaxError;
use Flames\Template\Node\Expression\ArrayExpression;
use Flames\Template\Node\Expression\BlockReferenceExpression;
use Flames\Template\Node\Expression\ConstantExpression;
use Flames\Template\Node\Expression\FunctionExpression;
use Flames\Template\Node\Expression\GetAttrExpression;
use Flames\Template\Node\Expression\MethodCallExpression;
use Flames\Template\Node\Expression\NameExpression;
use Flames\Template\Node\Expression\TestExpression;
use Flames\Template\Node\Node;

/**
 * @internal
 */
class DefinedTest extends TestExpression
{
    public function __construct(Node $node, string $name, ?Node $arguments, int $lineno)
    {
        if ($node instanceof NameExpression) {
            $node->setAttribute('is_defined_test', true);
        } elseif ($node instanceof GetAttrExpression) {
            $node->setAttribute('is_defined_test', true);
            $this->changeIgnoreStrictCheck($node);
        } elseif ($node instanceof BlockReferenceExpression) {
            $node->setAttribute('is_defined_test', true);
        } elseif ($node instanceof FunctionExpression && 'constant' === $node->getAttribute('name')) {
            $node->setAttribute('is_defined_test', true);
        } elseif ($node instanceof ConstantExpression || $node instanceof ArrayExpression) {
            $node = new ConstantExpression(true, $node->getTemplateLine());
        } elseif ($node instanceof MethodCallExpression) {
            $node->setAttribute('is_defined_test', true);
        } else {
            throw new SyntaxError('The "defined" test only works with simple variables.', $lineno);
        }

        parent::__construct($node, $name, $arguments, $lineno);
    }

    private function changeIgnoreStrictCheck(GetAttrExpression $node)
    {
        $node->setAttribute('optimizable', false);
        $node->setAttribute('ignore_strict_check', true);

        if ($node->getNode('node') instanceof GetAttrExpression) {
            $this->changeIgnoreStrictCheck($node->getNode('node'));
        }
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->subcompile($this->getNode('node'));
    }
}
