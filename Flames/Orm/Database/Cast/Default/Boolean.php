<?php

namespace Flames\Orm\Database\Cast\Default;

class Boolean
{
    public static function pre($column, $value)
    {
        if ($column->nullable === true && $value === null) {
            return null;
        }

        if ($value === true) {
            return 1;
        }

        return 0;
    }

    public static function pos($column, $value)
    {
        if ($column->nullable === true && $value === null) {
            return null;
        }

        if ($value === 1 || $value === 1.0 || $value === '1') {
            return true;
        }

        if ($value === 0 || $value === 0.0 || $value === '0') {
            return false;
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if ($value === -1 || $value === -1.0 || $value === '-1') {
            if ($column->nullable === false) {
                return $column->default;
            }
            return null;
        }

        return (bool)$value;
    }
}