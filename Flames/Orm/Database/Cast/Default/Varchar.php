<?php

namespace Flames\Orm\Database\Cast\Default;

class Varchar
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
        
        $value = (string)$value;
        if ($column->size !== null && strlen($value) > $column->size) {
            $value = substr($value, 0, $column->size);
        }

        return $value;
    }
}