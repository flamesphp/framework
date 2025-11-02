<?php

namespace Flames\Orm\Database\Cast\Meilisearch;

class FloatEx
{
    public static function pre($column, $value)
    {
        if ($column->nullable === true && $value === null) {
            return null;
        }

        return (float)$value;
    }

    public static function pos($column, $value)
    {
        if ($column->nullable === true && $value === null) {
            return null;
        }

        return (float)$value;
    }
}