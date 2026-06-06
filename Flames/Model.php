<?php
declare(strict_types=1);


namespace Flames;

use Error;
use Flames\Collection\Arr;
use Flames\Orm\Database;
use Flames\Orm\Model\Data;

/**
 * Class Model
 *
 * The Model class is an abstract class that serves as the base for all model classes in the application.
 * It provides methods to handle saving, retrieving, and manipulating data in the database.
 *
 */
abstract class Model
{
    private static array $__setup  = [];
    private static array $_driver   = [];
    private static array $_data = [];
    private static array $_cast = [];
    private static array $_connection = [];

    private array|null $__changed = null;

    public function save() : void
    {
        $class = static::class;

        $indexColumn = null;
        foreach (self::$_data[$class]->column as $column) {
            if ($column->primary === true || $column->autoIncrement === true) {
                $indexColumn = $column;
                break;
            }
        }
        if ($indexColumn === null) {
            foreach (self::$_data[$class]->column as $column) {
                if ($column->unique === true) {
                    $indexColumn = $column;
                    break;
                }
            }
        }

        if ($indexColumn === null) {
            throw new Error('Missing primary or unique column in table ' . self::$table . ' using class ' . static::class . '.');
        }

        $data = $this->toArray();

        if ($data[$indexColumn->property] === null) {
            self::_verifyConnection($class);

            /** @var Database\QueryBuilder\DefaultEx $queryBuilder */
            $queryBuilder = self::$_driver[self::$_data[$class]->database]->getQueryBuilder($class);
            $queryBuilder->setModel(static::class);

            $insert = $queryBuilder->insert($data);
            foreach ($insert as $key => $value) {
                $this->{$key} = $value;
            }

            $this->__changed = null;
            return;
        }

        if ($this->__changed === null || count($this->__changed) === 0) {
            return;
        }

        foreach ($data as $key => $_) {
            if (in_array($key, $this->__changed) === false) {
                unset($data[$key]);
            }
        }

        $data[$indexColumn->property] = $this->{$indexColumn->property};

        self::_verifyConnection($class);

        /** @var Database\QueryBuilder\DefaultEx $queryBuilder */
        $queryBuilder = self::$_driver[self::$_data[$class]->database]->getQueryBuilder($class);
        $queryBuilder->setModel(static::class);
        $queryBuilder->where($indexColumn->property, $this->{$indexColumn->property});
        $queryBuilder->update($data);
        $this->__changed = null;
    }

    public function getChanged(bool $onlyKeys = true) : Arr|null
    {
        if ($this->__changed === null) {
            return null;
        }

        if ($onlyKeys === true) {
            return Arr($this->__changed);
        }

        $data = Arr();
        foreach ($this->__changed as $key) {
            $data[$key] = $this->{$key};
        }
        return $data;
    }

    public function toArr() : Arr
    {
        return Arr($this->toArray());
    }

    public function toArray() : array
    {
        $class = static::class;
        $data = [];

        foreach (self::$_data[$class]->column as $column) {
            try {
                $data[$column->property] = $this->{$column->property};
            } catch (\Error $_) {
                $data[$column->property] = null;
            }
        }

        return $data;
    }

    public static function getTable() : string|null
    {
        $class = static::class;
        if (isset(static::$table[$class]) === false) {
            return null;
        }

        return self::$table[$class];
    }

    public static function getDatabase() : string|null
    {
        $class = static::class;
        if (isset(self::$_data[$class]) === false) {
            return null;
        }

        return self::$_data[$class]->database;
    }

    public static function __constructStatic(): void
    {
        $class = static::class;
        if (isset(static::$__setup[$class]) === true && static::$__setup[$class] === true) {
            return;
        }

        self::__setup(Data::mountData(static::class));
        self::$__setup[$class] = true;
    }

    private static function __setup(Arr $data): void
    {
        $class = static::class;
        self::$_data[$class] = $data;
        if (self::$_data[$class]->column->length === 0) {
            throw new \Exception('Model ' . static::class . 'need at least one column.');
        }
    }

    public function __construct(Arr|array|null $data = null, bool $ignoreChanged = false)
    {
        if ($data instanceof Arr) {
            $data = (array)$data;
        }

        if (is_array($data) === true) {
            foreach ($data as $key => $value) {
                try {
                    $this->__set($key, $value);
                } catch (\TypeError $_) {}
            }
        }

        $data = $this->toArray();
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }

        if ($ignoreChanged === true) {
            $this->__changed = null;
        }
    }

    public function __set(string $key, mixed $value)
    {
        $class = static::class;

        if (isset(self::$_data[$class]->column[$key]) === true) {
            try {
                $this->{$key} = self::cast($key, $value);
            } catch (\TypeError $e) {}

            if ($this->__changed === null) {
                $this->__changed = [];
            }

            if (in_array($key, $this->__changed) === false) {
                $this->__changed[] = $key;
            }
        }
    }

    public function set(string $key, mixed $value): void
    {
        $this->__set($key, $value);
    }

    public function __get(string $key)
    {
        if (isset($this->{$key}) === true) {
            return $this->{$key};
        }

        return null;
    }

    public function get(string $key)
    {
        return $this->__get($key);
    }

    public static function cast(string $key, mixed $value = null) : mixed
    {
        $class = static::class;

        if (isset(self::$_data[$class]->column[$key]) === false) {
            throw new \Exception('Model key ' . $key . ' not found in class ' . $class);
        }

        self::_verifyCast($class);
        return self::$_cast[$class]::pos(self::$_data[$class]->column[$key], $value);
    }

    public static function getDriver(): mixed
    {
        $class = static::class;

        $database = self::$_data[$class]->database;
        if ($database === null) {
            $database = sha1($config);
        }

        if (isset(self::$_driver[$database]) === false || self::$_driver[$database] === null) {
            $_driver = new static();
            $_driver::_verifyConnection($class);
        }
        return self::$_driver[$database];
    }

    private static function _verifyConnection(string $class)
    {
        if (isset(self::$_connection[$class]) === false || self::$_connection[$class] === false) {
            self::$_connection[$class] = false;

            $database = self::$_data[$class]->database;
            if ($database === null) {
                $database = sha1($config);
            }

            $driver = Database\Driver::getByConfigAndDatabase(
                Database\DataFactory::getConfigByDatabase($database),
                self::$_data[$class]->database
            );

            $driver->migrate(self::$_data[$class]);
            self::$_driver[$database] = $driver;
        }
    }

    private static function _verifyCast(string $class)
    {
        if (isset(self::$_cast[$class]) === false || self::$_cast[$class] === false) {
            self::$_cast[$class] = Database\Cast\Factory::getByDatabaseType(
                Database\DataFactory::getConfigByDatabase(self::$_data[$class]->database)->type
            );
        }
    }

    public static function getMetadata($verifyConnection = false)
    {
        $class = static::class;

        if ($verifyConnection === true) {
            self::_verifyConnection($class);
            return true;
        }

        return self::$_data[$class];
    }
}
