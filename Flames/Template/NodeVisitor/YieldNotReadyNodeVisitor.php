<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\NodeVisitor;

use Flames\Template\Attribute\YieldReady;
use Flames\Template\Environment;
use Flames\Template\Node\Expression\AbstractExpression;
use Flames\Template\Node\Node;
use function Flames\Template\NodeVisitor\trigger_deprecation;

/**
 * @internal to be removed in Template 4
 */
final class YieldNotReadyNodeVisitor implements NodeVisitorInterface
{
    private $useYield;
    private $yieldReadyNodes = [];

    public function __construct(bool $useYield)
    {
        $this->useYield = $useYield;
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        $class = \get_class($node);

        if ($node instanceof AbstractExpression || isset($this->yieldReadyNodes[$class])) {
            return $node;
        }

        if (!$this->yieldReadyNodes[$class] = (bool) (new \ReflectionClass($class))->getAttributes(YieldReady::class)) {
            if ($this->useYield) {
                throw new \LogicException(sprintf('You cannot enable the "use_yield" option of Template as node "%s" is not marked as ready for it; please make it ready and then flag it with the #[YieldReady] attribute.', $class));
            }

            trigger_deprecation('twig/twig', '3.9', 'Template node "%s" is not marked as ready for using "yield" instead of "echo"; please make it ready and then flag it with the #[YieldReady] attribute.', $class);
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    public function getPriority(): int
    {
        return 255;
    }
}
