<?php

namespace Flames\Cli\Command;

use Flames\Cli\Output;

/**
 * @internal
 *
 * Interactive database shell (à la Laravel `php artisan db`).
 *
 * Usage:
 *   forge db                         — open shell for the default database
 *   forge db sql {sql}               — run SQL on the default database
 *   forge db {connection}            — open shell for a named connection
 *   forge db {connection} sql {sql}  — run SQL on a named connection
 *
 * Connection config is read from ROOT_PATH/.env using the pattern:
 *   DATABASE_DEFAULT=mariadb
 *   DATABASE_MARIADB_DRIVER=mariadb
 *   DATABASE_MARIADB_HOST=127.0.0.1
 *   DATABASE_MARIADB_PORT=3306
 *   DATABASE_MARIADB_NAME=mydb
 *   DATABASE_MARIADB_USER=root
 *   DATABASE_MARIADB_PASSWORD=secret
 */
final class Db
{
    protected string|null $connection = null;
    protected string|null $sql        = null;
    protected string|null $mode       = null;  // null=shell, model-list, wipe, truncate, migrate

    public function __construct($data)
    {
        // argv layout after "db":
        //   db                         → shell for default db
        //   db sql {sql}               → run SQL on default db
        //   db sql {connection} {sql}  → run SQL on named connection
        //   db {conn}                  → shell for named connection
        //   db model list              → list all models
        //   db model list {connection} → list models for specific connection
        //   db wipe                    → drop all tables (default connection)
        //   db wipe {connection}       → drop all tables (specific connection)
        //   db truncate                → truncate all tables except migrations
        //   db truncate {connection}
        //   db migrate                 → force-migrate all models
        //   db migrate {connection}
        $args = array_values(array_slice($_SERVER['argv'], 2));

        if (empty($args)) {
            return;
        }

        if ($args[0] === 'model' && isset($args[1]) && $args[1] === 'list') {
            $this->mode       = 'model-list';
            $this->connection = $args[2] ?? null;
            return;
        }

        if ($args[0] === 'wipe') {
            $this->mode       = 'wipe';
            $this->connection = $args[1] ?? null;
            return;
        }

        if ($args[0] === 'truncate') {
            $this->mode       = 'truncate';
            $this->connection = $args[1] ?? null;
            return;
        }

        if ($args[0] === 'migrate') {
            $this->mode       = 'migrate';
            $this->connection = $args[1] ?? null;
            return;
        }

        if ($args[0] === 'sql') {
            // Distinguish: db sql {connection} {sql}  vs  db sql {sql}
            // Connection names are simple identifiers; SQL starts with a keyword.
            $sqlStarters = [
                'select','insert','update','delete','create','drop','alter',
                'show','describe','explain','truncate','replace','with',
                'set','use','call','begin','commit','rollback','grant','revoke',
            ];
            if (isset($args[1]) && isset($args[2])
                && !in_array(strtolower($args[1]), $sqlStarters, true)) {
                $this->connection = $args[1];
                $this->sql        = implode(' ', array_slice($args, 2));
            } else {
                $this->sql = implode(' ', array_slice($args, 1));
            }
            return;
        }

        $this->connection = $args[0];
    }

    public function run(bool $debug = false): bool
    {
        if ($this->mode === 'model-list') {
            return $this->runModelList();
        }
        if ($this->mode === 'wipe') {
            return $this->runWipe();
        }
        if ($this->mode === 'truncate') {
            return $this->runTruncate();
        }
        if ($this->mode === 'migrate') {
            return $this->runMigrate();
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $env = self::readEnv();

        $connName = $this->connection ?? ($env['DATABASE_DEFAULT'] ?? null);
        if ($connName === null) {
            Output::error('DATABASE_DEFAULT not set in .env');
            return false;
        }

        $prefix = 'DATABASE_' . strtoupper($connName) . '_';
        $driver = strtolower($env[$prefix . 'DRIVER'] ?? $connName);
        $host   = $env[$prefix . 'HOST']     ?? '127.0.0.1';
        $port   = $env[$prefix . 'PORT']     ?? null;
        $name   = $env[$prefix . 'NAME']     ?? null;
        $user   = $env[$prefix . 'USER']     ?? null;
        $pass   = $env[$prefix . 'PASSWORD'] ?? '';

        if ($name === null || $user === null) {
            Output::error("Connection '{$connName}' is not fully configured in .env.");
            return false;
        }

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                if (!self::commandExists('mysql')) {
                    Output::error("MySQL client not found. Install it with: apt install default-mysql-client");
                    return false;
                }
                return $this->runMysql($host, (int)($port ?? 3306), $name, $user, $pass);

            case 'pgsql':
            case 'postgresql':
            case 'postgres':
                if (!self::commandExists('psql')) {
                    Output::error("PostgreSQL client not found. Install it with: apt install postgresql-client");
                    return false;
                }
                return $this->runPgsql($host, (int)($port ?? 5432), $name, $user, $pass);

            case 'sqlite':
            case 'sqlite3':
                if (!self::commandExists('sqlite3')) {
                    Output::error("SQLite3 client not found. Install it with: apt install sqlite3");
                    return false;
                }
                $path = $env[$prefix . 'PATH'] ?? $name;
                return $this->runSqlite($path);

            default:
                Output::error("Unsupported driver '{$driver}'. Supported: mysql/mariadb, pgsql, sqlite.");
                return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Driver implementations
    // ─────────────────────────────────────────────────────────────────────────

    protected function runMysql(string $host, int $port, string $db, string $user, string $pass): bool
    {
        $cmd = 'mysql'
            . ' -h' . escapeshellarg($host)
            . ' -P' . $port
            . ' -u' . escapeshellarg($user)
            . ' -p' . escapeshellarg($pass)
            . ' '   . escapeshellarg($db);

        if ($this->sql !== null) {
            $cmd .= ' -e ' . escapeshellarg($this->sql);
        }

        passthru($cmd, $code);
        return $code === 0;
    }

    protected function runPgsql(string $host, int $port, string $db, string $user, string $pass): bool
    {
        $env = 'PGPASSWORD=' . escapeshellarg($pass);
        $cmd = $env . ' psql'
            . ' -h ' . escapeshellarg($host)
            . ' -p ' . $port
            . ' -U ' . escapeshellarg($user)
            . ' -d ' . escapeshellarg($db);

        if ($this->sql !== null) {
            $cmd .= ' -c ' . escapeshellarg($this->sql);
        }

        passthru($cmd, $code);
        return $code === 0;
    }

    protected function runSqlite(string $path): bool
    {
        $cmd = 'sqlite3 ' . escapeshellarg($path);

        if ($this->sql !== null) {
            $cmd .= ' ' . escapeshellarg($this->sql);
        }

        passthru($cmd, $code);
        return $code === 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Wipe (drop all tables)
    // ─────────────────────────────────────────────────────────────────────────

    protected function runWipe(): bool
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $env      = self::readEnv();
        $connName = $this->connection ?? ($env['DATABASE_DEFAULT'] ?? null);
        if ($connName === null) {
            Output::error('DATABASE_DEFAULT not set in .env');
            return false;
        }

        $pdo = $this->buildPdo($connName, $env);
        if ($pdo === null) {
            return false;
        }

        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($tables)) {
            Output::info("No tables found in '{$connName}'.");
            return true;
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
            echo '  ' . Output::RED . 'dropped' . Output::RESET . '  ' . $table . "\n";
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        echo "\n";
        Output::success("Wiped '{$connName}': " . count($tables) . ' tables dropped.');
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Truncate (empty all tables, reset auto-increment)
    // ─────────────────────────────────────────────────────────────────────────

    protected function runTruncate(): bool
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $env      = self::readEnv();
        $connName = $this->connection ?? ($env['DATABASE_DEFAULT'] ?? null);
        if ($connName === null) {
            Output::error('DATABASE_DEFAULT not set in .env');
            return false;
        }

        $pdo = $this->buildPdo($connName, $env);
        if ($pdo === null) {
            return false;
        }

        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($tables)) {
            Output::info("No tables found in '{$connName}'.");
            return true;
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $count = 0;
        foreach ($tables as $table) {
            if ($table === 'flames_migration') {
                continue;
            }
            $pdo->exec('TRUNCATE TABLE `' . $table . '`');
            // TRUNCATE resets AUTO_INCREMENT automatically in MySQL/MariaDB.
            // For safety, also issue an explicit reset.
            $pdo->exec('ALTER TABLE `' . $table . '` AUTO_INCREMENT = 1');
            echo '  ' . Output::YELLOW . 'truncated' . Output::RESET . '  ' . $table . "\n";
            $count++;
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        echo "\n";
        Output::success("Truncated '{$connName}': {$count} tables cleared.");
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Migrate (force ORM migration for all models)
    // ─────────────────────────────────────────────────────────────────────────

    protected function runMigrate(): bool
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $env         = self::readEnv();
        $defaultConn = strtolower($env['DATABASE_DEFAULT'] ?? 'default');
        $filterConn  = $this->connection !== null ? strtolower($this->connection) : null;

        $models = $this->discoverModels($env, $defaultConn);

        if (empty($models)) {
            Output::warning('No models found.');
            return true;
        }

        $byConn = [];
        foreach ($models as $model) {
            $conn = $model['connection'];
            if ($filterConn !== null && strtolower($conn) !== $filterConn) {
                continue;
            }
            $byConn[$conn][] = $model;
        }

        if (empty($byConn)) {
            Output::warning("No models found for connection '{$filterConn}'.");
            return true;
        }

        foreach ($byConn as $conn => $connModels) {
            echo Output::CYAN . Output::BOLD . "  {$conn}" . Output::RESET . "\n";

            $config = \Flames\Orm\Database\DataFactory::getConfigByDatabase($conn);
            $rawConn = \Flames\Orm\Database\RawConnection::getByConfigAndDatabase($config, $conn);

            // Use a fresh driver instance so the migration cache is empty.
            $driverClass = match ($config->type) {
                'mariadb' => \Flames\Orm\Database\Driver\MariaDb::class,
                'mysql'   => \Flames\Orm\Database\Driver\MySql::class,
                default   => null,
            };

            if ($driverClass === null) {
                Output::warning("Driver '{$config->type}' not supported for forced migration.");
                continue;
            }

            $driver = new $driverClass($rawConn);

            foreach ($connModels as $model) {
                $data = \Flames\Orm\Model\Data::mountData($model['class']);
                try {
                    $driver->migrate($data);
                    echo '    ' . Output::GREEN . '✔' . Output::RESET . '  ' . $model['table'] . "\n";
                } catch (\Throwable $e) {
                    echo '    ' . Output::RED . '✘' . Output::RESET . '  ' . $model['table']
                        . Output::GRAY . '  ' . $e->getMessage() . Output::RESET . "\n";
                }
            }
        }

        echo "\n";
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PDO factory
    // ─────────────────────────────────────────────────────────────────────────

    protected function buildPdo(string $connName, array $env): ?\PDO
    {
        $prefix = 'DATABASE_' . strtoupper($connName) . '_';
        $driver = strtolower($env[$prefix . 'DRIVER'] ?? $connName);
        $host   = $env[$prefix . 'HOST']     ?? '127.0.0.1';
        $port   = (int)($env[$prefix . 'PORT'] ?? 3306);
        $name   = $env[$prefix . 'NAME']     ?? null;
        $user   = $env[$prefix . 'USER']     ?? null;
        $pass   = $env[$prefix . 'PASSWORD'] ?? '';

        if ($name === null || $user === null) {
            Output::error("Connection '{$connName}' is not fully configured in .env.");
            return null;
        }

        try {
            return new \PDO(
                "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                $user,
                $pass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\PDOException $e) {
            Output::error("Could not connect to '{$connName}': " . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Model listing
    // ─────────────────────────────────────────────────────────────────────────

    protected function runModelList(): bool
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $env         = self::readEnv();
        $defaultConn = strtolower($env['DATABASE_DEFAULT'] ?? 'default');

        $models = $this->discoverModels($env, $defaultConn);

        if (empty($models)) {
            echo Output::YELLOW . '  No models found.' . Output::RESET . "\n";
            return true;
        }

        $filtered = [];
        foreach ($models as $model) {
            $conn = $model['connection'] ?? $defaultConn;
            if ($this->connection !== null && strtolower($conn) !== strtolower($this->connection)) {
                continue;
            }
            $filtered[] = $model;
        }

        if (empty($filtered)) {
            echo Output::YELLOW . "  No models found for connection '{$this->connection}'." . Output::RESET . "\n";
            return true;
        }

        usort($filtered, fn($a, $b) => strcmp($a['connection'] . $a['table'], $b['connection'] . $b['table']));

        $tableW = max(array_map(fn($m) => strlen($m['table']), $filtered));
        $tableW = max($tableW, 5);
        $connW  = max(array_map(fn($m) => strlen($m['connection']), $filtered));
        $connW  = max($connW, 10);
        $drvW   = max(array_map(fn($m) => strlen($m['driver']), $filtered));
        $drvW   = max($drvW, 6);

        $hdr = '  ' . Output::WHITE . Output::BOLD
            . str_pad('TABLE', $tableW) . '   '
            . str_pad('CONNECTION', $connW) . '   '
            . str_pad('DRIVER', $drvW)
            . Output::RESET;
        $sep = '  '
            . str_repeat('─', $tableW) . '   '
            . str_repeat('─', $connW) . '   '
            . str_repeat('─', $drvW);

        echo "\n$hdr\n$sep\n";

        foreach ($filtered as $m) {
            echo '  ' . Output::CYAN  . str_pad($m['table'], $tableW) . Output::RESET
               . '   ' . Output::GRAY . str_pad($m['connection'], $connW) . Output::RESET
               . '   ' . Output::GRAY . $m['driver'] . Output::RESET . "\n";
        }

        echo "\n";
        return true;
    }

    protected function discoverModels(array $env = [], string $defaultConn = 'default'): array
    {
        $scanDirs = [];

        $appDir = ROOT_PATH . 'App/Server/Model';
        if (is_dir($appDir)) {
            $scanDirs[] = ['dir' => $appDir, 'nsBase' => 'App\\Server\\Model'];
        }

        $msBase = ROOT_PATH . 'Microservice';
        if (is_dir($msBase)) {
            foreach (scandir($msBase) as $ms) {
                if ($ms === '.' || $ms === '..') continue;
                $msModelDir = $msBase . '/' . $ms . '/Server/Model';
                if (is_dir($msModelDir)) {
                    $scanDirs[] = ['dir' => $msModelDir, 'nsBase' => 'Microservice\\' . $ms . '\\Server\\Model'];
                }
            }
        }

        $models = [];

        foreach ($scanDirs as ['dir' => $dir, 'nsBase' => $nsBase]) {
            $this->scanModelsDir($dir, $nsBase, $defaultConn, $env, $models);
        }

        return $models;
    }

    protected function scanModelsDir(string $dir, string $nsBase, string $defaultConn, array $env, array &$models): void
    {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $dir . '/' . $file;
            if (is_dir($fullPath)) {
                $this->scanModelsDir($fullPath, $nsBase . '\\' . $file, $defaultConn, $env, $models);
                continue;
            }
            if (!str_ends_with($file, '.php')) continue;

            $className = $nsBase . '\\' . basename($file, '.php');

            try {
                if (!class_exists($className)) {
                    require_once $fullPath;
                }
                if (!class_exists($className)) continue;

                $ref   = new \ReflectionClass($className);
                $table = null;
                $conn  = null;

                foreach ($ref->getAttributes() as $attr) {
                    if ($attr->getName() === \Flames\Orm\Database::class) {
                        $args = $attr->getArguments();
                        if (isset($args['name'])) $conn = $args['name'];
                    }
                    if ($attr->getName() === \Flames\Orm\Table::class) {
                        $args = $attr->getArguments();
                        if (isset($args['name'])) $table = $args['name'];
                    }
                }

                if ($table === null) {
                    $table = str_replace('\\', '_', strtolower(substr($className, 17)));
                }

                $connName = $conn ?? $defaultConn;
                $prefix   = 'DATABASE_' . strtoupper($connName) . '_';
                $driver   = strtolower($env[$prefix . 'DRIVER'] ?? $connName);

                $models[] = [
                    'class'      => $className,
                    'table'      => $table,
                    'connection' => $connName,
                    'driver'     => $driver,
                ];
            } catch (\Throwable) {
                // skip unloadable classes
            }
        }
    }

    protected static function commandExists(string $cmd): bool
    {
        return !empty(shell_exec('which ' . escapeshellarg($cmd) . ' 2>/dev/null'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // .env reader (simple KEY=VALUE parser)
    // ─────────────────────────────────────────────────────────────────────────

    protected static function readEnv(): array
    {
        $path = ROOT_PATH . '.env';
        if (file_exists($path) === false) {
            return [];
        }

        $result = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key   = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            // Strip surrounding quotes
            if (strlen($value) >= 2 &&
                (($value[0] === '"' && $value[-1] === '"') ||
                 ($value[0] === "'" && $value[-1] === "'"))) {
                $value = substr($value, 1, -1);
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
