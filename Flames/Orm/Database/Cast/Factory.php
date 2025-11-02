<?php

namespace Flames\Orm\Database\Cast;

/**
 * @internal
 */
class Factory
{
    public static function getByDatabaseType(string $type)
    {
        $type[0] = strtoupper($type[0]);
        $class = ('Flames\\Orm\\Database\\Cast\\' . $type);

        $found = false;
        try {
            if (class_exists($class) === false) {
                $found = true;
            }
        } catch (\Exception $_) {
            $class = 'Flames\\Orm\\Database\\Cast\\DefaultEx';
        }

        return $class;
    }
}