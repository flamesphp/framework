<?php

namespace Flames\Orm\Database\RawConnection;

use PDO as NativePdo;

/**
 * @internal
 */
class Pdo extends NativePdo
{
    protected $config = null;
    protected $databaseSid = null;

    public function __construct(string $dsn, string|null $username = null, string|null $password = null, array|null $options = null, $config = null, $databaseSid = null)
    {
        $this->config = $config;
        $this->databaseSid = $databaseSid;

        parent::__construct($dsn, $username, $password, $options);

        $this->setAttribute(NativePdo::ATTR_ERRMODE, NativePdo::ERRMODE_EXCEPTION);
        $this->setAttribute(NativePdo::ATTR_EMULATE_PREPARES, false);
    }

    public function getConfig() { return $this->config; }
    public function getDatabaseSid() { return $this->databaseSid; }
}