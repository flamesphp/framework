<?php

namespace Flames\Orm\Database\Cast;

/**
 * @internal
 */
class DefaultEx
{
    public static function pre($column, $value, $fromDb = false)
    {
        $class = self::getClass($column);
        return $class::pre($column, $value, $fromDb);
    }

    public static function pos($column, $value, $fromDb = false)
    {
        $class = self::getClass($column);
        return $class::pos($column, $value, $fromDb);
    }

    private static function getClass($column)
    {
        $type = $column->type;
        $type[0] = strtoupper($type[0]);

        if ($type === 'Bool') {
            $type = 'BoolEx';
        }
        elseif ($type === 'Int') {
            $type = 'IntEx';
        }
        elseif ($type === 'Float') {
            $type = 'FloatEx';
        }

        $class = ('Flames\\Orm\\Database\\Cast\\Default\\' . $type);

        $found = false;
        try {
            if (class_exists($class) === false) {
                $found = true;
            }
        } catch (\Exception $_) {
            $class = 'Flames\\Orm\\Database\\Cast\\Default\\StringEx';
        }

        return $class;
    }
}