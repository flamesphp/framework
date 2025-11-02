<?php

namespace Flames\Collection;

/**
 * Represents an array object with additional utility methods.
 *
 * @property int $length
 * @property int $count
 * @property int $first
 * @property int $last
 */
final class Arr extends \ArrayObject
{
    /**
     * Class constructor.
     *
     * @param mixed $value [optional] The initial value to set. Default value is null.
     * @return void
     */
    public function __construct(mixed $value = null)
    {
        if ($value === null || (is_array($value) === false && ($value instanceof Arr) === false)) {
            $value = [];
        }

        parent::__construct($value, \ArrayObject::ARRAY_AS_PROPS);
    }

    public static function fromObject(object $object)
    {
        $array = self::__parseObjectToArray($object);
        return new self($array);
    }

    private static function __parseObjectToArray($value, bool $parseChildren = false)
    {
        $value = (array)$value;
        foreach ($value as $key => $_value) {
            if (is_array($_value) === true || is_object($_value) === true) {
                $value[$key] = self::__parseObjectToArray($_value);
            }
        }

        return $value;
    }

    /**
     * Get the string representation of the object.
     *
     * @return string The string representation of the object
     */
    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Retrieves a value at a specified key.
     *
     * @param mixed $key The key of the value to retrieve.
     * @return mixed The value at the specified key.
     */
    public function offsetGet(mixed $key) : mixed
    {
        $key = (string)$key;

        if (($key === 'length' || $key === 'count') && !parent::offsetExists($key))
            return $this->count();
        elseif ($key === 'first' && !parent::offsetExists($key))
            return $this->getFirst();
        elseif ($key === 'last' && !parent::offsetExists($key))
            return $this->getLast();

        return @parent::offsetGet($key);
    }

    /**
     * Set the value at the specified key.
     *
     * @param mixed $key The key to set the value at.
     * @param mixed $value The value to be set.
     * @return void
     */
    public function offsetSet(mixed $key, mixed $value) : void
    {
        $key = (string)$key;

        if (empty($key) === true) {
            $lastNumberKey = $this->getLastNumberKey();
            if ($lastNumberKey === null) {
                $key = 0;
            } else {
                $key = ($lastNumberKey + 1);
            }
        }

        parent::offsetSet($key, $value);
    }

    /**
     * Returns the length of the array.
     *
     * @return int The length of the array.
     */
    public function length() : int
    {
        return $this->count();
    }

    /**
     * Checks if the string contains the given value.
     *
     * @param mixed $value The value to search for.
     * @return bool Returns true if the value is found, false otherwise.
     */
    public function contains(mixed $value) : bool
    {
        foreach ($this as $_ => $_value) {
            if ($value === $_value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the given key exists in the collection.
     *
     * @param mixed $key The key to check.
     * @return bool True if the key exists, false otherwise.
     */
    public function containsKey(mixed $key) : bool
    {
        $key = (string)$key;
        return $this->offsetExists($key);
    }

    /**
     * Returns the last numeric key from the array.
     * If the array is empty or does not contain any numeric keys, NULL is returned.
     *
     * @return int|null The last numeric key of the array, or NULL if the array is empty or does not contain any numeric keys.
     */
    public function getLastNumberKey() : int|null
    {
        $topKey = 0;
        $keys = $this->getKeys();
        foreach ($keys as $key) {
            $key = (int)$key;
            if ($key > $topKey) {
                $topKey = $key;
            }
        }

        if ($topKey === 0) {
            if ($this->count === 0) {
                return null;
            }
        }

        return $topKey;
    }

    /**
     * Adds a value to the array.
     *
     * @param mixed $value The value to add to the array.
     * @param bool $canDuplicate Determines whether the value can be added multiple times. Defaults to true.
     * @return Arr The updated array.
     */
    public function add($value, $canDuplicate = true) : Arr
    {
        if ($canDuplicate === false && $this->contains($value)) {
            return $this;
        }

        $this[] = $value;
        return $this;
    }

    /**
     * Adds a key-value pair to the array.
     *
     * @param mixed $key The key to add.
     * @param mixed $value The value to associate with the key.
     * @return Arr The modified array.
     */
    public function addKey(mixed $key, mixed $value) : Arr
    {
        $key = (string)$key;
        $this[$key] = $value;
        return $this;
    }

    /**
     * Removes the element with the specified key from the array.
     *
     * @param mixed $key The key of the element to be removed.
     * @return Arr The updated array after removing the element.
     */
    public function removeKey(mixed $key) : Arr
    {
        $key = (string)$key;

        if (empty($key) !== true && (isset($this[$key]) || $this->offsetExists($key))) {
            unset($this[$key]);
        }

        return $this;
    }

    public function remove(mixed $value) : Arr
    {
        foreach ($this as $key => $_value) {
            if ($value === $_value) {
                unset($this[$key]);
            }
        }

        return $this;
    }

    /**
     * Sorts the array in ascending order.
     *
     * @return Arr The sorted array.
     */
    public function sort() : Arr
    {
        $this->asort();
        return $this;
    }

    /**
     * Sorts the array by keys in ascending order.
     *
     * @return Arr The sorted array by keys.
     */
    public function sortByKey() : Arr
    {
        $this->ksort();
        return $this;
    }

    /**
     * Sorts the array using the provided delegate.
     *
     * @param mixed $delegate The delegate used for sorting the array. The delegate should be a callable
     *                        that accepts two parameters ($a, $b) representing two elements of the array,
     *                        and returns a value indicating the comparison between the two elements.
     *                        If the delegate returns a boolean value, `true` indicates that $a should be
     *                        considered greater than $b, `false` indicates that $a should be considered
     *                        smaller than $b, and `null` indicates that $a and $b are equal. Any other
     *                        return value will be treated as `null`.
     * @return Arr The sorted array.
     */
    public function sortByDelegate(\Closure $delegate) : Arr
    {
        $this->uasort(function (mixed $a, mixed $b) use($delegate) {

            $response = $delegate($a, $b);

            if ($response === false) {
                $response = -1;
            }
            elseif ($response === true) {
                $response = 1;
            }
            elseif ($response === null) {
                $response = 0;
            }
            if ($response !== -1 && $response !== 1 && $response !== 0) {
                $response = 0;
            }

            return $response;
        });
        return $this;
    }

    /**
     * Converts the object to an array.
     *
     * @param bool $convertChildrens Determines whether to recursively convert children objects to arrays. Default is true.
     * @return array The converted array representation of the object.
     */
    public function toArray(bool $convertChildrens = true)
    {
        $array = $this->getArrayCopy();

        foreach ($array as $key => &$value) {
            if ($value instanceof Arr) {
                $value = $value->toArray();
            }
        }

        return $array;
    }

    /**
     * Searches for an element in the array that satisfies the given delegate.
     *
     * @param mixed $delegate The delegate to determine if an element meets the search criteria.
     * @param bool $isKeyValue Optional. Indicates if the delegate accepts both key and value parameters. Default is false.
     * @return mixed|null The first element that satisfies the delegate or null if no element is found.
     */
    public function find(\Closure $delegate, bool $isKeyValue = false) : mixed
    {
        if ($isKeyValue === false) {
            foreach ($this as  $value) {
                $isValid = $delegate($value);
                if ($isValid === true) {
                    return $value;
                }
            }
            return null;
        }

        foreach ($this as $key => $value)  {
            $isValid = $delegate($key, $value);
            if ($isValid === true) {
                return Arr(['key' => $key, 'value' => $value]);
            }
        }

        return null;
    }

    /**
     * Retrieves the keys of the array.
     *
     * @return Arr An Arr object containing the keys of the array.
     */
    public function getKeys() : Arr
    {
        $keys = array_keys((array)$this);
        if ($keys != null) {
            return Arr($keys);
        }
        return Arr();
    }

    /**
     * Returns the last element of the array.
     *
     * @return mixed|null The last element of the array if it is not empty, otherwise null.
     */
    public function getLast() : mixed
    {
        if ($this->count <= 0)  {
            return null;
        }
        $keys = $this->getKeys();
        return ($this[($keys[$keys->count - 1])]);
    }

    /**
     * Returns the first element of the array.
     *
     * If the array is empty, null will be returned.
     *
     * @return mixed The first element of the array.
     */
    public function getFirst() : mixed
    {
        if ($this->count <= 0) {
            return null;
        }
        $keys = $this->getKeys();
        return ($this[($keys[0])]);
    }

    /**
     * Merges the given array with the current array.
     *
     * @param Arr|array|null $array The array to be merged. It can be an instance of Arr class, or a regular PHP array.
     * @param bool $replace Whether to replace the existing values with the new values. Default is true.
     * @return Arr The array after merging.
     */
    public function merge(Arr|array $array = null, $replace = true)
    {
        $thisArray = $this->toArray();
        $array = Arr($array)->toArray();

        $newArray = (($replace === true)
            ? array_replace_recursive($thisArray, $array)
            : array_merge_recursive($thisArray, $array)
        );

        return Arr($newArray);
    }

    public function clone()
    {
        return clone $this;
    }
}