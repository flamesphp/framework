<?php

/*
 * This file is part of Template.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flames\Template\Extension;

use Flames\Template\Environment;
use Flames\Template\Template;
use Flames\Template\TemplateFunction;
use Flames\Template\TemplateWrapper;

/**
 * @internal
 */
final class DebugExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        // dump is safe if var_dump is overridden by xdebug
        $isDumpOutputHtmlSafe = \extension_loaded('xdebug')
            // false means that it was not set (and the default is on) or it explicitly enabled
            && (false === \ini_get('xdebug.overload_var_dump') || \ini_get('xdebug.overload_var_dump'))
            // false means that it was not set (and the default is on) or it explicitly enabled
            // xdebug.overload_var_dump produces HTML only when html_errors is also enabled
            && (false === \ini_get('html_errors') || \ini_get('html_errors'))
            || 'cli' === \PHP_SAPI
        ;

        return [
            new TemplateFunction('dump', [self::class, 'dump'], ['is_safe' => $isDumpOutputHtmlSafe ? ['html'] : [], 'needs_context' => true, 'needs_environment' => true, 'is_variadic' => true]),
        ];
    }

    /**
     * @internal
     */
    public static function dump(Environment $env, $context, ...$vars)
    {
        if (!$env->isDebug()) {
            return;
        }

        ob_start();

        if (!$vars) {
            $vars = [];
            foreach ($context as $key => $value) {
                if (!$value instanceof Template && !$value instanceof TemplateWrapper) {
                    $vars[$key] = $value;
                }
            }

            var_dump($vars);
        } else {
            var_dump(...$vars);
        }

        return ob_get_clean();
    }
}
