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

    public static function stringfy(mixed $serialize)
    {
        return serialize($serialize);
    }
}