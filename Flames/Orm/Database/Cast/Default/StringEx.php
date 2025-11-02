<?php

namespace Flames\Orm\Database\Cast\Default;

class StringEx
{
    public static function pre($column, $value)
    {
        if ($column->nullable === true && $value === null) {
            return null;
        }
        
        return (string)$value;
    }

    public static function pos($column, $value)
    {
        if ($column->nullable === true && $value === null) {
            return null;
        }

        return (string)$value;
    }
}