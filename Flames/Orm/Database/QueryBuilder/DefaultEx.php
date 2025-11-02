<?php

namespace Flames\Orm\Database\QueryBuilder;

use Flames\Collection\Arr;
use Flames\Environment;
use Flames\Orm\Database\Driver\MariaDb;
use Flames\Orm\Database\Driver\MySql;
use PDO;
use Exception;

/**
 * @internal
 */
class DefaultEx
{
    public function __construct($connection) {}

    public function setTable(string $table) { return $this; }

    public function setModel(string $model) { return $this; }

    public function where(string $key, mixed $valueOrCondition = null, mixed $value = null) { return $this; }

    public function orWhere(string $key, mixed $valueOrCondition = null, mixed $value = null) { return $this; }

    public function whereGroup(callable $delegate, Arr|array|null $values = null) { return $this; }

    public function orWhereGroup(callable $delegate, Arr|array|null $values = null) { return $this; }

    public function whereLike(string $key, mixed $value = null) { return $this; }

    public function orWhereLike(string $key, mixed $value = null) { return $this; }

    public function get(): Arr { return Arr(); }

    public function update(Arr|array $data) { return $this; }

    public function insert(Arr|array $data) { return null; }

    public function whereRaw(string $condition, mixed $values = null) { return $this; }

    public function orWhereRaw(string $condition, mixed $values = null) { return $this; }

    public function limit(int $limit) { return $this; }

    public function offset(int $offset) { return $this; }

    public function paginate(int $limit, int $page) { return $this; }
}
