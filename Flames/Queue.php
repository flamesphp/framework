<?php

namespace Flames;

use Flames\Collection\Arr;
use Flames\Queue\Data;
use Flames\Orm\Database;

/**
 * This class is an abstract Controller class that provides methods for handling requests and generating responses.
 */
abstract class Queue
{
    public static function add(mixed $data): void
    {
        $class = static::class;

        static::_verifyConnection($class);
        $driver = self::$_driver[self::$_data[$class]->database];
        $connection = $driver->getConnection();
        if (($connection instanceof \Flames\Orm\Database\RawConnection\Pdo) === false) {
            throw new \Exception('Queue currently only supports databases with PDO conneciton.');
        }

        $table = self::$_data[$class]->table;

        $statment = $connection->prepare('INSERT INTO `' . $table . '` (`data`, `date`) VALUES (:data, \'' . ((new DateTime())->format('Y-m-d H:i:s')) . '\')');
        $statment->execute(['data' => serialize($data)]);
    }

    public static function run(): void
    {
        $class = static::class;

        static::_verifyProcess($class);
        static::_verifyConnection($class);

        $driver = self::$_driver[self::$_data[$class]->database];
        $connection = $driver->getConnection();
        if (($connection instanceof \Flames\Orm\Database\RawConnection\Pdo) === false) {
            throw new \Exception('Queue currently only supports databases with PDO conneciton.');
        }

        self::_onLoop($class, $connection);
    }

    public static function removeById(int $id): void
    {
        $class = static::class;

        static::_verifyProcess($class);
        static::_verifyConnection($class);

        $driver = self::$_driver[self::$_data[$class]->database];
        $connection = $driver->getConnection();
        if (($connection instanceof \Flames\Orm\Database\RawConnection\Pdo) === false) {
            throw new \Exception('Queue currently only supports databases with PDO conneciton.');
        }

        $table = self::$_data[$class]->table;

        $connection->query('DELETE FROM `' . $table . '` WHERE `id` = \'' . $id . '\' LIMIT 1;');
    }

    public static function onMessage(int $id, mixed $data): void {}

    private static array $__setup = [];
    private static array $_data = [];
    private static array $_driver = [];
    private static array $_connection = [];
    private static array $_migrate = [];
    private static array $_process = [];

    /**
     * Initializes the static constructor.
     *
     * @return void
     * @internal
     */
    public static function __constructStatic(): void
    {
        $class = static::class;
        if (isset(static::$__setup[$class]) === true && static::$__setup[$class] === true) {
            return;
        }

        static::__setup(Data::mountData($class), $class);
        static::$__setup[$class] = true;
    }

    /**
     * Sets up the class methods.
     *
     * @param Arr $data The data object containing methods information.
     * @param string $class The class name.
     *
     * @return void
     * @internal
     */
    private static function __setup(Arr $data, string $class): void
    {
        static::$_data[$class] = $data;
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

            self::$_driver[$database] = $driver;
            self::$_data[$class]->database = $database;
            self::$_connection[$class] = true;
        }

        if (isset(self::$_migrate[$class]) === false || self::$_migrate[$class] === false) {
            $driver->migrateQueue(self::$_data[$class]->table);
            self::$_migrate[$class] = true;
        }
    }

    private static function _verifyProcess($class)
    {
        if (isset(self::$_process[$class]) === false) {
            self::$_process[$class] = sha1(
                \Flames\Server::getUniqueId() .
                \Flames\Process::getCurrent()->getPid()
            );
        }
    }

    private static function _onLoop($class, $connection)
    {
        $processId = self::$_process[$class];
        $table = self::$_data[$class]->table;
        $timeout = self::$_data[$class]->timeout;
        $timeLimit = self::$_data[$class]->timelimit;
        $delay = self::$_data[$class]->delay;
        $currentTime = microtime(true);
        $timeLimitFinal = ($currentTime + $timeLimit);

        do {
            $dateNow = (new \DateTimeImmutable());
            $dateNowFormatted = $dateNow->format('Y-m-d H:i:s');

            $dateTimeout = $dateNow->modify('-' . $timeout . ' second');
            $dateTimeoutFormatted = $dateTimeout->format('Y-m-d H:i:s');

            $connection->query('UPDATE `' . $table . '` SET `process_id` = \'' .
                $processId . '\', `date` = \'' . $dateNowFormatted . '\'
                    WHERE
                    (`process_id` IS NULL) OR
                    (`date` < \'' . $dateTimeoutFormatted . '\') 
                    LIMIT 1;
                ');

            $query = $connection->query('SELECT `id`, `data` FROM `' . $table . '` WHERE `process_id` = \'' .
                $processId . '\' LIMIT 1;');

            $data = $query->fetchAll();

            if ($data !== false && count($data) > 0) {
                $id = (int)$data[0]['id'];
                $parsedData = unserialize($data[0]['data']);

                static::onMessage($id, $parsedData);

                $connection->query('UPDATE `' . $table . '` SET `process_id` = NULL
                    WHERE `id` = \'' . $id . '\';');
            }

            if ((microtime(true) + $delay) > $timeLimitFinal) {
                break;
            }

            usleep($delay * 1000000);
        } while (true);
    }
}