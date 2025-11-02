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
use Flames\Template\Node\Expression\AbstractExpression;

/**
 * @internal
 */
#[YieldReady]
class ExportNode extends Node implements NodeOutputInterface
{
    public function __construct(AbstractExpression $expr, ?AbstractExpression $variables, bool $only, bool $ignoreMissing, int $lineno, ?string $tag = null)
    {
        $nodes = ['expr' => $expr];
        if (null !== $variables) {
            $nodes['variables'] = $variables;
        }

        parent::__construct($nodes, ['only' => $only, 'ignore_missing' => $ignoreMissing], $lineno, $tag);
    }
}
