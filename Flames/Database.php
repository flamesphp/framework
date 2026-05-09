<?php

namespace Flames;

use Flames\Orm\Database\DataFactory;
use Flames\Orm\Database\RawConnection\Pdo;

/**
 * Utility class for managing databases at the infrastructure level
 * (create, activate tenant connections, etc.).
 */
class Database
{
    /**
     * Creates a database using privileged credentials for the given database
     * key. Reads DATABASE_{KEY}_ROOT_USER / DATABASE_{KEY}_ROOT_PASSWORD from
     * the environment when available (falls back to the regular user).
     * After creating the database, GRANTs full privileges to the regular user
     * so subsequent ORM connections work normally.
     *
     * Example:
     *   Database::activate('tenant', 'club_0000000001');
     *   Database::create('tenant');
     *
     * @param string $databaseKey  The key used in .env (e.g. 'tenant')
     */
    public static function create(string $databaseKey): void
    {
        $config  = DataFactory::getConfigByDatabase($databaseKey);
        $keyUp   = strtoupper($databaseKey);

        $rootUser = Environment::get('DATABASE_' . $keyUp . '_ROOT_USER')
                 ?: $config->user;
        $rootPass = Environment::get('DATABASE_' . $keyUp . '_ROOT_PASSWORD')
                 ?: $config->password;

        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=utf8mb4',
            $config->host,
            $config->port
        );

        $pdo = new Pdo($dsn, $rootUser, $rootPass);

        $dbName = $config->name;

        $pdo->exec(
            "CREATE DATABASE IF NOT EXISTS `{$dbName}`
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        // Grant the regular user full access to the new database
        if ($rootUser !== $config->user) {
            $pdo->exec(
                "GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$config->user}'@'%'"
            );
            $pdo->exec('FLUSH PRIVILEGES');
        }
    }

    /**
     * Points the given database key to a new database name at runtime.
     * Updates the environment variable and invalidates the DataFactory cache
     * so subsequent ORM calls use the new database.
     *
     * Example:
     *   Database::activate('tenant', 'club_0000000001');
     *
     * @param string $databaseKey  e.g. 'tenant'
     * @param string $name         The target database name
     */
    public static function activate(string $databaseKey, string $name): void
    {
        $envKey = 'DATABASE_' . strtoupper($databaseKey) . '_NAME';
        Environment::set($envKey, $name);
        DataFactory::invalidate($databaseKey);
    }

    /**
     * Drops a database using privileged credentials for the given database key.
     * Does nothing if the database does not exist.
     *
     * @param string $databaseKey  e.g. 'tenant'
     * @param string $name         The database name to drop
     */
    public static function drop(string $databaseKey, string $name): void
    {
        $config  = DataFactory::getConfigByDatabase($databaseKey);
        $keyUp   = strtoupper($databaseKey);

        $rootUser = Environment::get('DATABASE_' . $keyUp . '_ROOT_USER')
                 ?: $config->user;
        $rootPass = Environment::get('DATABASE_' . $keyUp . '_ROOT_PASSWORD')
                 ?: $config->password;

        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=utf8mb4',
            $config->host,
            $config->port
        );

        $pdo = new Pdo($dsn, $rootUser, $rootPass);
        $pdo->exec("DROP DATABASE IF EXISTS `{$name}`");
    }

    /**
     * Checks whether a database exists on the server of the given key.
     * Uses root credentials when available for reliable INFORMATION_SCHEMA access.
     *
     * @param string $databaseKey  e.g. 'tenant'
     * @param string $name         Database name to check
     */
    public static function exists(string $databaseKey, string $name): bool
    {
        $config  = DataFactory::getConfigByDatabase($databaseKey);
        $keyUp   = strtoupper($databaseKey);

        $rootUser = Environment::get('DATABASE_' . $keyUp . '_ROOT_USER')
                 ?: $config->user;
        $rootPass = Environment::get('DATABASE_' . $keyUp . '_ROOT_PASSWORD')
                 ?: $config->password;

        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=utf8mb4',
            $config->host,
            $config->port
        );

        $pdo  = new Pdo($dsn, $rootUser, $rootPass);
        $stmt = $pdo->prepare(
            'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?'
        );
        $stmt->execute([$name]);

        return $stmt->fetch() !== false;
    }
}
