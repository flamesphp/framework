<?php

namespace Flames\Orm\Database\Driver;

/**
 * @internal
 */
class Mariadb extends MySql
{
    public function getQueryBuilder($model)
    {
        $metadata = $model::getMetadata();
        return new \Flames\Orm\Database\QueryBuilder\MariaDb($this->connection);
    }
}
