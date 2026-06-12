<?php

declare(strict_types=1);

namespace App;

class App
{
    public const MODULE = MODULE;

    public static function isServer(): bool
    {
        return self::MODULE === 'SERVER';
    }
}
