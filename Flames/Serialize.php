<?php

namespace Flames;

class Serialize
{
    public static function parse(?string $serialize = null)
    {
        if ($serialize === null) {
            return null;
        }

        return unserialize($serialize);
    }

    public static function stringify(mixed $serialize)
    {
        return serialize($serialize);
    }
}