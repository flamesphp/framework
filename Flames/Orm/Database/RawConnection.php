<?php

namespace Flames\Orm\Database;

use Flames\Collection\Arr;
use Flames\Environment;
use Flames\Orm\Database\Driver\MariaDB;
use Flames\Orm\Database\Driver\MySQL;
use Http\Client;
use PDOException;

/**
 * @internal
 */
class RawConnection
{
    protected static $connections = [];

    public static function getByConfigAndDatabase($config, string $database = null)
    {
        if ($database === null) {
            $database = sha1($config);
        }

        if (isset(self::$connections[$database]) === true) {
            return self::$connections[$database];
        }

        // PDO Connection
        if ($config->type === 'mariadb' || $config->type === 'mysql') {
            try {
                $connectionUri = ('mysql:host='. $config->host . ';dbname=' . $config->name . ';port=' . $config->port . ';charset=utf8mb4');
                $connection = new RawConnection\Pdo($connectionUri, $config->user, $config->password, null, $config, $database);
                self::$connections[$database] = $connection;
            } catch(PDOException $e) {
                throw new \Error($e->getMessage());
            }
        }
        elseif ($config->type === 'meilisearch') {
            $connectionUri = ('http://'. $config->host . ':' . $config->port . '/');
            $connection = new RawConnection\Meilisearch($connectionUri, $config->masterKey, $config);
            self::$connections[$database] = $connection;
        }

        return self::$connections[$database];
    }
}
