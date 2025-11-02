<?php

namespace Flames\Orm\Database;

use Flames\Collection\Arr;
use Flames\Environment;
use Flames\Orm\Database\QueryBuilder\MariaDb;
use Flames\Orm\Database\QueryBuilder\MySql;
use PDO;
use Exception;

/**
 * @internal
 */
class QueryBuilder
{
    public static function getByTypeAndConnection($type, $connection)
    {
        if ($type === 'mariadb') {
            return new MariaDb($connection);
        }
        elseif ($type === 'mysql') {
            return new MySql($connection);
        }
    }
}
