<?php

namespace Flames;

class Json
{
    public static function parse(?string $json = null)
    {
        if ($json === null) {
            return null;
        }

        return json_decode($json);
    }

    public static function stringify(mixed $data)
    {
        return json_encode($data);
    }
}