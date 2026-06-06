<?php
declare(strict_types=1);


namespace Flames;

use Flames\Orm\Database\DataFactory;
use Flames\Orm\Database\RawConnection\Pdo;

/**
 * Utility class for managing databases at the infrastructure level
 * (create, activate tenant connections, etc.).
 */
class Database
{
    public static function create(string $databaseKey): void
    {
        $config  = DataFactory::getConfigByDatabase($databaseKey);
        $keyUp   = strtoupper($databaseKey);

        $rootUser = Environment::get('DATABASE_' . $keyUp . '_ROOT_USER') ?: $config->user;
        $rootPass = Environment::get('DATABASE_' . $keyUp . '_ROOT_PASSWORD') ?: $config->password;

        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config->host, $config->port);
        $pdo = new Pdo($dsn, $rootUser, $rootPass);
        $dbName = $config->name;

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        if ($rootUser !== $config->user) {
            $pdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$config->user}'@'%'");
            $pdo->exec('FLUSH PRIVILEGES');
        }
    }

    public static function activate(string $databaseKey, string $name): void
    {
        $envKey = 'DATABASE_' . strtoupper($databaseKey) . '_NAME';
        Environment::set($envKey, $name);
        DataFactory::invalidate($databaseKey);
    }

    public static function drop(string $databaseKey, string $name): void
    {
        $config   = DataFactory::getConfigByDatabase($databaseKey);
        $keyUp    = strtoupper($databaseKey);
        $rootUser = Environment::get('DATABASE_' . $keyUp . '_ROOT_USER') ?: $config->user;
        $rootPass = Environment::get('DATABASE_' . $keyUp . '_ROOT_PASSWORD') ?: $config->password;

        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config->host, $config->port);
        $pdo = new Pdo($dsn, $rootUser, $rootPass);
        $pdo->exec("DROP DATABASE IF EXISTS `{$name}`");
    }

    public static function exists(string $databaseKey, string $name): bool
    {
        $config   = DataFactory::getConfigByDatabase($databaseKey);
        $keyUp    = strtoupper($databaseKey);
        $rootUser = Environment::get('DATABASE_' . $keyUp . '_ROOT_USER') ?: $config->user;
        $rootPass = Environment::get('DATABASE_' . $keyUp . '_ROOT_PASSWORD') ?: $config->password;

        $dsn  = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config->host, $config->port);
        $pdo  = new Pdo($dsn, $rootUser, $rootPass);
        $stmt = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
        $stmt->execute([$name]);

        return $stmt->fetch() !== false;
    }
}
