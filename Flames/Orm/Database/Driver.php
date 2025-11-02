<?php

namespace Flames\Orm\Database;

use Flames\Collection\Arr;
use Flames\Environment;
use Flames\Orm\Database\Driver\MariaDb;
use Flames\Orm\Database\Driver\Meilisearch;
use Flames\Orm\Database\Driver\MySql;
use PDO;
use Exception;

/**
 * @internal
 */
class Driver
{
    protected static $drivers = [];

    public static function getByConfigAndDatabase($config, string $database = null)
    {
        if ($database === null) {
            $database = sha1($config);
        }

        if (isset(self::$drivers[$database]) === true) {
            return self::$drivers[$database];
        }

        $rawConnection = RawConnection::getByConfigAndDatabase($config, $database);

        if ($config->type === 'mariadb') {
            self::$drivers[$database] = new MariaDb($rawConnection);
            return self::$drivers[$database];
        }

        if ($config->type === 'mysql') {
            self::$drivers[$database] = new MySql($rawConnection);
            return self::$drivers[$database];
        }

        if ($config->type === 'meilisearch') {
            self::$drivers[$database] = new Meilisearch($rawConnection);
            return self::$drivers[$database];
        }

        throw new Exception('Database driver ' . $config->type . ' not implemented yet.');
    }
}
