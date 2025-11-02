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
final class SecurityNotAllowedMethodError extends SecurityError
{
    private $className;
    private $methodName;

    public function __construct(string $message, string $className, string $methodName)
    {
        parent::__construct($message);
        $this->className = $className;
        $this->methodName = $methodName;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName()
    {
        return $this->methodName;
    }
}
