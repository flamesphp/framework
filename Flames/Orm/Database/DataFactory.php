<?php

namespace Flames\Orm\Database;

use Flames\Collection\Strings;
use Flames\Environment;

/**
 * @internal
 */
class DataFactory
{
    protected static $databases = [];

    public static function getConfigByDatabase($database = null)
    {
        if ($database === null) {
            $database = Environment::get('DATABASE_DEFAULT');
        }

        if (isset(self::$databases[$database]) === true) {
            return self::$databases[$database];
        }


        $databaseUpper = strtoupper($database);

        $data = Arr();
        $data->type = Strings::toLower(Environment::get('DATABASE_' . $databaseUpper . '_DRIVER'));

        if ($data->type === 'mariadb' || $data->type === 'mysql') {
            $data->name = (Environment::get('DATABASE_' . $databaseUpper . '_NAME'));
            $data->host = (Environment::get('DATABASE_' . $databaseUpper . '_HOST'));
            $data->port = (Environment::get('DATABASE_' . $databaseUpper . '_PORT'));
            $data->user = (Environment::get('DATABASE_' . $databaseUpper . '_USER'));
            $data->password = (Environment::get('DATABASE_' . $databaseUpper . '_PASSWORD'));
        }
        elseif ($data->type === 'meilisearch') {
            $data->host = (Environment::get('DATABASE_' . $databaseUpper . '_HOST'));
            $data->port = (Environment::get('DATABASE_' . $databaseUpper . '_PORT'));
            $data->masterKey = (Environment::get('DATABASE_' . $databaseUpper . '_MASTER_KEY'));
        }

        self::$databases[$database] = $data;
        return self::$databases[$database];
    }

    /**
     * Removes the cached config for the given database key so that the next
     * call to getConfigByDatabase() re-reads the environment variables.
     * Useful after changing DATABASE_*_NAME at runtime via Environment::set().
     */
    public static function invalidate(string $database): void
    {
        unset(self::$databases[$database]);
    }
}