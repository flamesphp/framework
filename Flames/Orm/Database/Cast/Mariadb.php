<?php

namespace Flames\Orm\Database\Cast;

/**
 * @internal
 */
class MariaDb extends DefaultEx
{
    public static function pre($column, $value, $fromDb = false)
    {
        return parent::pre($column, $value, $fromDb);
    }

    public static function pos($column, $value, $fromDb = false)
    {
        return parent::pos($column, $value, $fromDb);
    }
}