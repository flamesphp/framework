<?php

namespace Flames\Orm\Database\QueryBuilder;

use Flames\Collection\Arr;
use Flames\Environment;
use PDO;
use Exception;

/**
 * @internal
 */
class MySql extends DefaultEx
{
    protected static $tableColumns = [];

    protected $mode = 'table';
    protected $connection;
//    protected $databasePrefix = '';

    protected $model = null;
    protected $modelData = null;
    protected $modelCast = null;
    protected $table = null;

    protected $wheres = [];
    protected $whereBaseIndex = '';
    protected $orders = [];
    protected $groups = [];
    protected $limit = null;
    protected $offset = null;

    public function __construct($connection)
    {
        $this->connection = $connection;

        // TODO: support multi database
//        $config = $this->connection->getConfig();
//        if ($config !== null && isset($config->name) === true) {
//            $this->databasePrefix = ('`' . $config->name . '`.');
//        }
    }

    public function setTable(string $table)
    {
        $this->mode = 'table';
        $this->table = $table;
        $this->model = null;

        return $this;
    }

    public function setModel(string $model)
    {
        $this->mode = 'model';
        $this->model = $model;
        $this->modelData = $model::getMetadata();
        $this->modelCast = \Flames\Orm\Database\Cast\Factory::getByDatabaseType(
            \Flames\Orm\Database\DataFactory::getConfigByDatabase($this->modelData->database)->type
        );
        $this->table = $this->modelData->table;

        return $this;
    }

    protected function _setBaseIndex($whereBaseIndex)
    {
        $this->whereBaseIndex = $whereBaseIndex;
    }

    public function where(string $key, mixed $valueOrCondition  = null, mixed $value = null)
    {
        $argsCount = func_num_args();
        if ($argsCount === 2) {
            return $this->_where('AND', $key, $valueOrCondition);
        }

        return $this->_where('AND', $key, $valueOrCondition, $value);
    }

    public function orWhere(string $key, mixed $valueOrCondition = null , mixed $value = null)
    {
        $argsCount = func_num_args();
        if ($argsCount === 2) {
            return $this->_where('OR', $key, $valueOrCondition);
        }

        return $this->_where('OR', $key, $valueOrCondition, $value);
    }

    public function whereGroup(callable $delegate, Arr|array|null $values = null)
    {
        return $this->_where('AND', $delegate, $values);
    }

    public function orWhereGroup(callable $delegate, Arr|array|null $values = null)
    {
        return $this->_where('OR', $delegate, $values);
    }

    protected function _where(string $operator, string|callable $keyOrFunction, mixed $valueOrCondition = null, $value = null)
    {
        $function = null;
        $key = null;
        $condition = '=';

        if (is_callable($keyOrFunction) === true) {
            $valueOrCondition = null;
            $value = null;
            $function = $keyOrFunction;

            $this->wheres[] = [
                'type' => 'delegate',
                'value' => $function,
                'operator' => $operator
            ];
            return $this;
        }
        elseif (is_string($keyOrFunction) === true) {
            $argsCount = func_num_args();
            $key = $keyOrFunction;

            if ($argsCount === 3) {
                $value = $valueOrCondition;
            } else {
                $condition = $valueOrCondition;
                $value = $value;
            }
        }
        else {
            throw new Exception('Invalid where parameters in table ' . $this->table  . '.');
        }

        if ($this->mode === 'model') {
            if (isset($this->modelData->column[$key]) === false) {
                throw new \Exception('Model key ' . $key . ' not found in class ' . $this->modelData->class);
            }

            $data = $this->_castDataPre([$key => $value]);

            $this->wheres[] = [
                'type' => 'default',
                'key' => $this->modelData->column[$key]->name,
                'condition' => $condition,
                'value' => $value,
                'operator' => $operator
            ];
        } else {
            $this->wheres[] = [
                'type' => 'default',
                'key' => $key,
                'condition' => $value,
                'value' => $value
            ];
        }

        return $this;
    }

    public function whereLike(string $key, mixed $value = null)
    {
        return $this->_whereLike('AND', $key, $value);
    }

    public function orWhereLike(string $key, mixed $value = null)
    {
        return $this->_whereLike('OR', $key, $value);
    }

    protected function _whereLike(string $operator, string $key, mixed $value = null)
    {
        if ($this->mode === 'model') {
            if (isset($this->modelData->column[$key]) === false) {
                throw new \Exception('Model key ' . $key . ' not found in class ' . $this->modelData->class);
            }

            $data = $this->_castDataPre([$key => $value]);

            $this->wheres[] = [
                'type' => 'default',
                'key' => $this->modelData->column[$key]->name,
                'condition' => 'LIKE',
                'value' => $value,
                'operator' => $operator
            ];
        } else {
            $this->wheres[] = [
                'type' => 'default',
                'key' => $key,
                'condition' => 'LIKE',
                'value' => $value,
                'operator' => $operator
            ];
        }

        return $this;
    }

    public function whereIn(string $key, mixed $value = null)
    {
        return $this->_whereIn('AND', $key, $value);
    }

    public function orWhereIn(string $key, mixed $value = null)
    {
        return $this->_whereIn('OR', $key, $value);
    }

    protected function _whereIn(string $operator, string $key, mixed $value = null)
    {
        if ($this->mode === 'model') {
            if (isset($this->modelData->column[$key]) === false) {
                throw new \Exception('Model key ' . $key . ' not found in class ' . $this->modelData->class);
            }

            $data = [];
            foreach ($value as $_value) {
                $data[] = $this->_castDataPre([$key => $_value]);
            }
            if (count($data) === 0) {
                throw new Exception('WhereIn for model key ' . $key . ' in class ' . $this->modelData->class . ' can\'t be empty.');
            }

            $this->wheres[] = [
                'type' => 'default',
                'key' => $this->modelData->column[$key]->name,
                'condition' => 'IN',
                'value' => $value,
                'operator' => $operator
            ];
        } else {
            $this->wheres[] = [
                'type' => 'default',
                'key' => $key,
                'condition' => 'IN',
                'value' => $value,
                'operator' => $operator
            ];
        }

        return $this;
    }


    public function whereRaw(string $condition, mixed $values = null)
    {
        return $this->_whereRaw('AND', $condition, $values);
    }

    public function orWhereRaw(string $condition, mixed $values = null)
    {
        return $this->_whereRaw('OR', $condition, $values);
    }

    protected function _whereRaw(string $operator, string $condition, mixed $values = null)
    {
        $this->wheres[] = [
            'type' => 'raw',
            'condition' => $condition,
            'value' => $values,
            'operator' => $operator
        ];
    }

    protected function _nativeWhere(Arr|array $data)
    {
        $query = '';

        if (count($this->wheres) > 0) {
            $firstWhere = true;
            $whereIndex = 0;

            foreach ($this->wheres as $where) {
                if ($firstWhere === true) {
                    $firstWhere = false;
                } else {
                    $query .= (' ' . $where['operator'] . ' ');
                }

                if ($where['type'] === 'default') {
                    $whereParam = (':where_' . $this->whereBaseIndex . $whereIndex . '_' . $where['key']);

                    if ($where['condition'] === 'IN') {
                        $whereParams = [];
                        foreach ($where['value'] as $value) {
                            $_whereParam = ('where_' . $this->whereBaseIndex . $whereIndex . '_' . $where['key']);
                            $whereParams[] = $_whereParam;
                            $data[$_whereParam] = $value;
                            $whereIndex++;
                        }

                        $whereParam = ('(:' . implode(', :', $whereParams) . ')');
                        $query .= ('`' . $this->table . '`.`' . $where['key'] . '` ' . $where['condition'] . ' ' . $whereParam . ' ');
                    } else {
                        if ($where['condition'] === 'LIKE') {
                            $whereParam = (
                                'CONCAT(\'%\', ' .
                                $whereParam .
                                ', \'%\')'
                            );
                        }

                        $query .= ('`' . $this->table . '`.`' . $where['key'] . '` ' . $where['condition'] . ' ' . $whereParam . ' ');
                        $data['where_' . $this->whereBaseIndex . $whereIndex . '_' . $where['key']] = $where['value'];
                        $whereIndex++;
                    }

                }
                elseif ($where['type'] === 'raw') {
                    if ($where['condition'] !== '') {
                        if ($where['value'] === null) {
                            $query .= (' (' . $where['condition'] . ') ');
                        } else {
                            foreach ($where['value'] as $key => $value) {
                                $whereParam = (':where_' . $this->whereBaseIndex . $whereIndex . '_' . $key);
                                $where['condition'] = str_replace('{' . $key . '}', $whereParam, $where['condition']);
                                $data['where_' . $this->whereBaseIndex . $whereIndex . '_' . $key] = $value;
                                $whereIndex++;
                            }

                            $query .= (' (' . $where['condition'] . ') ');
                        }
                    }
                }
                elseif ($where['type'] === 'delegate') {
                    $delegateQueryBuilder = new self($this->connection);
                    $delegateQueryBuilder->_setBaseIndex($this->whereBaseIndex . $whereIndex. '_');
                    if ($this->mode === 'model') { $delegateQueryBuilder->setModel($this->model); }
                    else { $delegateQueryBuilder->setTable($this->table); }

                    $delegate = $where['value'];
                    $delegate($delegateQueryBuilder);

                    $delegateWhereQuery = $delegateQueryBuilder->_nativeWhere([]);
                    $data = array_merge($data, $delegateWhereQuery['data']);

                    $query .= (' (' . $delegateWhereQuery['query'] . ') ');
                    $whereIndex++;
                }
            }

            $query = (substr($query, 0, -1) . "\r\n");
        }

        return ['data' => $data, 'query' => $query];
    }

    public function join(string $model, string $name, callable $delegate)
    {

    }

    protected function _nativeJoin()
    {
        return '';
    }

    public function order(string $key, string $direction = 'ASC')
    {
        if ($this->mode === 'model') {
            if (isset($this->modelData->column[$key]) === false) {
                throw new \Exception('Model key ' . $key . ' not found in class ' . $this->modelData->class);
            }

            $this->orders[] = [
                'type' => 'default',
                'key' => $this->modelData->column[$key]->name,
                'direction' => strtolower($direction)
            ];
        } else {
            $this->orders[] = [
                'type' => 'default',
                'key' => $key,
                'direction' => strtolower($direction)
            ];
        }

        return $this;
    }

    protected function _nativeOrder()
    {
        $query = '';

        if (count($this->orders) > 0) {
            foreach ($this->orders as $order) {
                if ($order['type'] === 'default') {
                    $query .= ('`' . $this->table . '`.`' . $order['key'] . '` ' . $order['direction'] . ',');
                }
            }
        }

        if ($query !== '') {
            $query = (substr($query, 0, -1) . "\r\n");
        }
        $query = str_replace(',', ",\r\n", $query);

        return $query;
    }

    public function group(string $key)
    {
        if ($this->mode === 'model') {
            if (isset($this->modelData->column[$key]) === false) {
                throw new \Exception('Model key ' . $key . ' not found in class ' . $this->modelData->class);
            }

            $this->groups[] = [
                'type' => 'default',
                'key' => $this->modelData->column[$key]->name
            ];
        } else {
            $this->groups[] = [
                'type' => 'default',
                'key' => $key
            ];
        }

        return $this;
    }

    protected function _nativeGroup()
    {
        $query = '';

        if (count($this->groups) > 0) {
            foreach ($this->groups as $group) {
                if ($group['type'] === 'default') {
                    $query .= ('`' . $this->table . '`.`' . $group['key'] . '`,');
                }
            }
        }

        if ($query !== '') {
            $query = (substr($query, 0, -1) . "\r\n");
        }
        $query = str_replace(',', ",\r\n", $query);

        return $query;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function paginate(int $limit, int $page)
    {
        $this->limit($limit);

        if ($page > 1) {
            $offset = ($page * $limit) - $limit;
            $this->offset($offset);
        }
        return $this;
    }

    public function get(): Arr
    {
        $data = [];
        $query = "SELECT \r\n";

        $querySelectors = '';
        $columns = $this->_getTableColumns();
        foreach ($columns as $column) {
            $querySelectors .= ('`' . $this->table . '`.`' . $column . '` as \'' . $this->table . '.' . $column . "',");
        }
        if ($querySelectors !== '') {
            $querySelectors = (substr($querySelectors, 0, -1) . "\r\n");
        }
        $query .= str_replace(',', ",\r\n", $querySelectors);
        // TODO: where joins

        $query .= (' FROM `' . $this->table . '` ');

        $query .= $this->_nativeJoin();

        $nativeWhere = $this->_nativeWhere($data);

        if ($nativeWhere['query'] !== '') {
            $query .= "\r\n WHERE \r\n";
        }
        $data = $nativeWhere['data'];
        $query .= $nativeWhere['query'];

        $nativeGroup = $this->_nativeGroup();
        if ($nativeGroup !== '') {
            $query .= ("\r\n GROUP BY \r\n" . $nativeGroup);
            if ($this->mode === 'model') {
                // TODO: group by (fix joins with multiples entries)
            }
        } else {
            if ($this->mode === 'model') {
                // TODO: group by (fix joins with multiples entries)
            }
        }

        $nativeOrder = $this->_nativeOrder();
        if ($nativeOrder !== '') {
            $query .= ("\r\n ORDER BY \r\n" . $nativeOrder);
        }

        if ($this->limit !== null) {
            $query .= ("\r\n LIMIT " . $this->limit);
        }
        if ($this->offset !== null) {
            $query .= ("\r\n OFFSET " . $this->offset);
        }

        $statement = $this->connection->prepare($query);
        $statement->execute($data);

        if ($this->mode === 'model') {
            $models = Arr();
            while ($row = $statement->fetch()) {
                $modelData = [];
                foreach ($this->modelData->column as $column) {
                    if (isset($row[$this->table . '.' . $column->name]) === true) {
                        $modelData[$column->property] = $row[$this->table . '.' . $column->name];
                    }
                }

                // TODO: join selects

                $modelData = $this->_castDataPos($modelData, true);
                $models[] = new $this->modelData->class($modelData, true);
            }

            return $models;
        }

        $rows = Arr();
        while ($row = $statement->fetch()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function update(Arr|array $data)
    {
        if ($this->mode === 'model') {
            $data = $this->_castDataPos($data);
            $data = $this->_castDataPre($data);
        }

        if (count($data) === 0) {
            throw new Exception('Update payload in table ' . $this->table . ' can\'t be empty.');
        }

        $query = ('UPDATE `' . $this->table . '` ');
        $query .= $this->_nativeJoin();
        $query .= ' SET ';

        if ($this->mode === 'model') {
            foreach ($data as $key => $value) {
                $query .= ('`' . $this->modelData->column[$key]->name . '` = :' . $key . ',');
            }
        } else {
            foreach ($data as $key => $_) {
                $query .= ('`' . $key . '` = :' . $key . ',');
            }
        }

        $query = (substr($query, 0, -1) . "\r\n");

        $nativeWhere = $this->_nativeWhere($data);
        if ($nativeWhere['query'] !== '') {
            $query .= "\r\n WHERE \r\n";
        }
        $data = $nativeWhere['data'];
        $query .= $nativeWhere['query'];

        $nativeOrder = $this->_nativeOrder();
        if ($nativeOrder !== '') {
            $query .= ("\r\n ORDER BY \r\n" . $nativeOrder);
        }

        if ($this->limit !== null) {
            $query .= ("\r\n LIMIT " . $this->limit);
        }

        $statement = $this->connection->prepare($query);
        $statement->execute($data);

        return true;
    }

    public function insert(Arr|array  $data)
    {
        if ($this->mode === 'model') {
            $data = $this->_castDataPos($data);
            $data = $this->_castDataPre($data);
        }

        if (count($data) === 0) {
            throw new Exception('Insert payload in table ' . $this->table  . ' can\'t be empty.');
        }

        $query = ('INSERT INTO `' . $this->table . '` ' . "\n\t(");

        if ($this->mode === 'model') {
            foreach ($data as $key => $_) {
                $query .= ('`' . $this->modelData->column[$key]->name . '`, ');
            }
        } else {
            foreach ($data as $key => $_) {
                $query .= ('`' . $key . '`, ');
            }
        }

        $query = (substr($query, 0, -2) . ")\nVALUES (");
        foreach ($data as $key => $_) {
            $query .= (':' . $key . ', ');
        }
        $query = (substr($query, 0, -2) . ');');
        $statement = $this->connection->prepare($query);

        $statement->execute($data);

        $insertId = $this->connection->lastInsertId();

        if ($this->mode === 'model') {
            $autoIncrementColumn = false;
            foreach ($this->modelData->column as $column) {
                if ($column->autoIncrement === true) {
                    $autoIncrementColumn = $column;
                    break;
                }
            }

            if ($autoIncrementColumn !== null) {
                $cast = $this->modelCast;
                return Arr([
                    $autoIncrementColumn->property => $cast::pos($autoIncrementColumn, $insertId)
                ]);
            }
        }

        return $insertId;
    }

    protected function _castDataPos(Arr|array $data, bool $fromDb = false)
    {
        $cast = $this->modelCast;

        foreach ($data as $key => $value) {
            if (isset($this->modelData->column[$key]) === false) {
                throw new \Exception('Model key ' . $key . ' not found in class ' . $this->modelData->class);
            }

            $data[$key] = $cast::pos($this->modelData->column[$key], $value, $fromDb);
        }

        return $data;
    }

    protected function _castDataPre(Arr|array $data)
    {
        $cast = $this->modelCast;

        foreach ($data as $key => $value) {
            if (isset($this->modelData->column[$key]) === false) {
                throw new \Exception('Model key ' . $key . ' not found in class ' . $this->modelData->class);
            }
            $data[$key] = $cast::pre($this->modelData->column[$key], $value);
        }

        return $data;
    }

    protected function _getTableColumns()
    {
        $dbColumns = [];

        if ($this->mode === 'table') {
            $query = $this->connection->query('SHOW COLUMNS FROM ' . $this->table . ';');
            $_dbColumns = $query->fetchAll();
            foreach ($_dbColumns as $dbColumn) {
                $dbColumns[] = $dbColumn['Field'];
            }
        } else {
            foreach ($this->modelData->column as $key => $column) {
                $dbColumns[] = $column['name'];
            }
        }

        self::$tableColumns[$this->table] = $dbColumns;
        return self::$tableColumns[$this->table];
    }
}
