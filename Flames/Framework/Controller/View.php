<?php
declare(strict_types=1);


namespace Flames\Framework\Controller;

/**
 * Marks a controller method whose array return value should be rendered
 * through a Mesh template located under Server/View/.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class View
{
    public function __construct(public readonly string $path)
    {
    }
}
