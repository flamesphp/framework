<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\Loader;

use Flames\Template\Error\LoaderError;
use Flames\Template\Source;

/**
 * @internal
 */
final class ArrayLoader implements LoaderInterface
{
    private $templates = [];

    /**
     * @param array $templates An array of templates (keys are the names, and values are the source code)
     */
    public function __construct(array $templates = [])
    {
        $this->templates = $templates;
    }

    public function setTemplate(string $name, string $template): void
    {
        $this->templates[$name] = $template;
    }

    public function getSourceContext(string $name): Source
    {
        if (!isset($this->templates[$name])) {
            throw new LoaderError(sprintf('Template "%s" is not defined.', $name));
        }

        return new Source($this->templates[$name], $name);
    }

    public function exists(string $name): bool
    {
        return isset($this->templates[$name]);
    }

    public function getCacheKey(string $name): string
    {
        if (!isset($this->templates[$name])) {
            throw new LoaderError(sprintf('Template "%s" is not defined.', $name));
        }

        return $name.':'.$this->templates[$name];
    }

    public function isFresh(string $name, int $time): bool
    {
        if (!isset($this->templates[$name])) {
            throw new LoaderError(sprintf('Template "%s" is not defined.', $name));
        }

        return true;
    }
}
