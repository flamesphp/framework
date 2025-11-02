<?php


namespace Flames;

use Flames\Collection\Arr;
use Flames\Environment\Helpers;

/**
 * Class Environment
 *
 * The Environment class represents a configuration environment,
 * allowing users to access and modify configuration data.
 *
 * @property bool $DUMP_ENABLED
 * @property string $DUMP_IDE
 * @property string $DUMP_THEME
 */
final class Environment
{
    protected static Arr|null $dataGlobal = null;
    protected static Environment|null $dataDefault = null;

    protected Arr|null $data = null;
    protected bool $valid = true;
    protected string|null $path;

    /**
     * Class constructor.
     *
     * Initializes a new instance of the class.
     *
     * @param mixed $path The path to the file. Defaults to null.
     *
     * @return void
     */
    public function __construct(mixed $path = null)
    {
        $path = (string)$path;

        if (empty($path) === true) {
            $path = (ROOT_PATH . '.env');
        }

        $this->path = $path;
        $this->load();
        if (self::$dataDefault === null) {
            self::$dataDefault = $this;
        }
    }

    /**
     * Magic getter method.
     *
     * Retrieves the value of the specified key from the environment array.
     *
     * @param mixed $key The key of the value to retrieve.
     *
     * @return mixed|null The value of the specified key if it exists, otherwise null.
     */
    public function __get(mixed $key) : mixed
    {
        $key = (string)$key;
        if (empty($key) === true) {
            return null;
        }

        if ($this->data->containsKey($key)) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * Magic method to set a value for a specific key.
     *
     * This method allows setting a value for a specific key in the environment array.
     *
     * @param mixed $key The key for which to set the value.
     * @param mixed $value The value to set for the specified key.
     *
     * @return void
     */
    public function __set(mixed $key, mixed $value) : void
    {
        $key = (string)$key;
        if (empty($key) === true) {
            return;
        }

        $this->data[$key] = $value;
    }

    /**
     * Returns the array of data elements.
     *
     * @return Arr The array of data elements.
     */
    public function getAll() : Arr
    {
        return $this->data;
    }

    /**
     * Returns the data elements as an array.
     *
     * @return array The data elements as an array.
     */
    public function toArray() : array
    {
        return (array)$this->data;
    }

    /**
     * Returns the default Environment object, or null if no default is set.
     *
     * @return Environment|null The default Environment object, or null if no default is set.
     */
    public static function default() : Environment|null
    {
        return self::$dataDefault;
    }

    /**
     * Retrieves the value associated with the given key from the environment data.
     *
     * @param string $key The key of the value to retrieve.
     * @return mixed|null The value associated with the key if found, or null if the key is empty or not found.
     */
    public static function get($key) : mixed
    {
        $key = (string)$key;
        if (empty($key) === true) {
            return null;
        }

        if (@self::$dataGlobal->containsKey($key)) {
            return self::$dataGlobal[$key];
        }

        return null;
    }

    /**
     * Sets a key-value pair in the environment data array.
     *
     * @param mixed $key The key to set.
     * @param mixed $value The value to set.
     * @return void
     */
    public static function set(mixed $key, mixed $value) : void
    {
        $key = (string)$key;
        if (empty($key) === true) {
            return;
        }

        if (self::$dataGlobal === null) {
            return;
        }

        self::$dataGlobal[$key] = $value;
    }

    /**
     * Injects the environment elements of the current object into the environment data container.
     *
     * This method iterates through the data elements of the current object and adds them to the environment data container.
     *
     * @return void
     */
    public function inject() : void
    {
        if (self::$dataGlobal === null) {
            self::$dataGlobal = Arr();
        }

        foreach ($this->data as $key => $value) {
            self::$dataGlobal[$key] = $value;
        }
    }

    /**
     * Save the data to the specified file path.
     *
     * It writes the updated content back to the file.
     *
     * @return void
     */
    public function save() : void
    {
        $data = str_replace(["\r\n", "\r"], "\n", file_get_contents($this->path));
        $mount = '';

        $keys = $this->data->getKeys()->toArray();
        $retriteKeys = [];

        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            if (str_starts_with($line, "\n") || str_starts_with($line, '#') || str_contains($line, '=') === false) {
                $mount .= ($line . "\n");
                continue;
            }

            $var = trim(explode('=', $line)[0]);

            foreach ($keys as $key) {
                if ($key === $var) {
                    $retriteKeys[] = $key;
                    break;
                }
            }

            $value = $this->{$var};
            if ($value === true) {
                $value = 'true';
            } elseif ($value === false) {
                $value = 'false';
            }

            if (str_contains($value, ' ')) {
                $value = ('"' . $value . '"');
            }
            $mount .= ($var . '=' . $value . "\n");
        }

        $missingKeys = array_diff($keys, $retriteKeys);
        foreach ($missingKeys as $key) {
            $value = $this->{$key};
            if ($value === true) {
                $value = 'true';
            } elseif ($value === false) {
                $value = 'false';
            }

            if (str_contains($value, ' ')) {
                $value = ('"' . $value . '"');
            }

            $mount .= ($key . '=' . $value . "\n");
        }

        @file_put_contents($this->path, $mount);
    }

    /**
     * Returns whether the object is valid or not.
     *
     * @return bool The validity status of the object.
     */
    public function isValid() : bool
    {
        return $this->valid;
    }

    /**
     * This method is responsible for loading data from a file.
     *
     * @return void
     */
    protected function load() : void
    {
        $basePath  = (ROOT_PATH . '.cache/environment/');
        $cachePath = ($basePath . sha1($this->path));

        if (file_exists($this->path) === false) {

            $this->valid = false;
            $this->data = Arr();
            return;
        }

        $currentTime = filemtime($this->path);

        if (file_exists($cachePath) === true && filemtime($cachePath) === $currentTime) {
            $this->data = unserialize(file_get_contents($cachePath));
            return;
        }

        $this->data = Arr(Helpers::fromFile($this->path));
        $success = @file_put_contents($cachePath, serialize($this->data));
        if ($success === false) {
            if (is_dir($basePath) === false) {
                $mask = umask(0);
                mkdir($basePath, 0777, true);
                umask($mask);
                @file_put_contents($cachePath, serialize($this->data));
            }
        }
        @touch($cachePath, $currentTime);
    }
}