<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\Sandbox;

/**
 * @internal
 */
interface SecurityPolicyInterface
{
    /**
     * @param string[] $tags
     * @param string[] $filters
     * @param string[] $functions
     *
     * @throws SecurityError
     */
    public function checkSecurity($tags, $filters, $functions): void;

    /**
     * @param object $obj
     * @param string $method
     *
     * @throws SecurityNotAllowedMethodError
     */
    public function checkMethodAllowed($obj, $method): void;

    /**
     * @param object $obj
     * @param string $property
     *
     * @throws SecurityNotAllowedPropertyError
     */
    public function checkPropertyAllowed($obj, $property): void;
}
