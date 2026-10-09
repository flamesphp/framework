<?php

/*
 * Bundled by Surface right after Kernel\Client\Virtual in the same eval:
 * must use bracketed namespace syntax and no declare(strict_types).
 */
namespace Flames\AutoLoad
{
    /**
     * @internal
     */
    final class Client
    {
        public static function run(): void
        {
            \spl_autoload_register(function ($name) {
                \Flames\Kernel\Client\Virtual::load(ltrim((string)$name, '\\'));
            });
        }
    }
}
