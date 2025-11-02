<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\TokenParser;

use Flames\Template\Node\DoNode;
use Flames\Template\Node\Node;
use Flames\Template\Token;

/**
 * Evaluates an expression, discarding the returned value.
 *
 * @internal
 */
final class DoTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();

        $this->parser->getStream()->expect(/* Token::BLOCK_END_TYPE */ 3);

        return new DoNode($expr, $token->getLine(), $this->getTag());
    }

    public function getTag(): string
    {
        return 'do';
    }
}
