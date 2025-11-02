<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flames\Template\Environment;
use Flames\Template\Extension\StringLoaderExtension;
use Flames\Template\TemplateWrapper;

/**
 * @internal
 *
 * @deprecated since Template 3.9
 */
function twig_template_from_string(Environment $env, $template, ?string $name = null): TemplateWrapper
{
    trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);

    return StringLoaderExtension::templateFromString($env, $template, $name);
}
