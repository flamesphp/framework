<?php

namespace Flames\Orm\Database\Cast\Meilisearch;

class Bigint
{
    public static function pre($column, $value)
    {
        if ($column->nullable === true && $value === null) {
            return null;
        }

        return (int)$value;
    }

    public static function pos($column, $value)
    {
        if ($column->nullable === true && $value === null) {
            return null;
        }

        return (int)$value;
    }
}