<?php

// Twig fork: https://github.com/twigphp/Twig

namespace Flames\Template;

use Flames\Template\Source\PostProcess;

/**
 * @internal
 */
final class Source
{
    private $code;
    private $name;
    private $path;

    /**
     * @param string $code The template source code
     * @param string $name The template logical name
     * @param string $path The filesystem path of the template if any
     */
    public function __construct(string $code, string $name, string $path = '')
    {
        $this->code = PostProcess::parse($code);
        $this->name = $name;
        $this->path = $path;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
