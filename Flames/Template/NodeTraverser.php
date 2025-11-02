<?php

// Twig fork: https://github.com/twigphp/Twig

namespace Flames\Template;

use Flames\Template\Node\Node;
use Flames\Template\NodeVisitor\NodeVisitorInterface;

/**
 * @internal
 */
final class NodeTraverser
{
    private $env;
    private $visitors = [];

    /**
     * @param NodeVisitorInterface[] $visitors
     */
    public function __construct(Environment $env, array $visitors = [])
    {
        $this->env = $env;
        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }
    }

    public function addVisitor(NodeVisitorInterface $visitor): void
    {
        $this->visitors[$visitor->getPriority()][] = $visitor;
    }

    /**
     * Traverses a node and calls the registered visitors.
     */
    public function traverse(Node $node): Node
    {
        ksort($this->visitors);
        foreach ($this->visitors as $visitors) {
            foreach ($visitors as $visitor) {
                $node = $this->traverseForVisitor($visitor, $node);
            }
        }

        return $node;
    }

    private function traverseForVisitor(NodeVisitorInterface $visitor, Node $node): ?Node
    {
        $node = $visitor->enterNode($node, $this->env);

        foreach ($node as $k => $n) {
            if (null !== $m = $this->traverseForVisitor($visitor, $n)) {
                if ($m !== $n) {
                    $node->setNode($k, $m);
                }
            } else {
                $node->removeNode($k);
            }
        }

        return $visitor->leaveNode($node, $this->env);
    }
}
