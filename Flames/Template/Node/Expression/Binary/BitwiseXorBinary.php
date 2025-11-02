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

namespace Flames\Template\Node\Expression\Binary;

use Flames\Template\Compiler;

/**
 * @internal
 */
class BitwiseXorBinary extends AbstractBinary
{
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('^');
    }
}
