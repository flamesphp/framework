<?php

namespace Flames;

use Flames\Collection\Arr;
use Flames\Crypto\Hash;
use Flames\Exception\Coroutine\Timeout;

/**
 * Class Coroutine
 *
 * The Coroutine class represents a set of coroutines that can be executed asynchronously.
 */
class Coroutine
{
    protected const BASE_FOLDER = '.cache/coroutine/';

    protected int $timeout;
    protected string $caller;
    protected array $coroutines = [];

    /**
     * Constructs a new instance of the class.
     *
     * @param int $timeout (Optional) The maximum execution time in milliseconds for each coroutine. Default is 0 (no timeout).
     */
    public function __construct(int $timeout = 0)
    {
        $this->caller = self::__getCaller();
        $this->timeout = $timeout;
    }

    /**
     * Add a new coroutine to the list of coroutines.
     *
     * @param string $method The name of the method to be executed in the coroutine.
     * @param mixed $args Optional. The arguments to be passed to the method. Default is null.
     *
     * @return void
     */
    public function add(string $method, mixed $args = null)
    {
        $args = func_get_args();
        array_splice($args, 0, 1);

        $serializedArgs = [];
        foreach ($args as $arg) {
            $serializedArgs[] = serialize($arg);
        }

        $this->coroutines[] = [
            'method' => $method,
            'args'   => $serializedArgs,
            'hash'   => Hash::getRandom()
        ];
    }

    /**
     * Executes the coroutines and returns the results.
     *
     * @param bool $echoOutput (Optional) Whether to echo the output of the coroutines. Default is false.
     * @return Arr The results returned from the coroutines.
     * @throws Timeout If any coroutine exceeds the maximum execution time.
     */
    public function run(bool $echoOutput = false) : Arr
    {
        $coroutinePath = (ROOT_PATH . self::BASE_FOLDER);
        if (is_dir($coroutinePath) === false) {
            $mask = umask(0);
            mkdir($coroutinePath, 0777, true);
            umask($mask);
        }

        $results = Arr();
        $coroutinesWaiting = [];

        foreach ($this->coroutines as $coroutine) {
            $coroutine['caller'] = $this->caller;
            file_put_contents($coroutinePath . $coroutine['hash'], serialize($coroutine));

            $socketPath = ($coroutinePath . sha1($coroutine['hash']));
            file_put_contents($socketPath, '');

            $coroutinesWaiting[] = [
                'coroutine' => $coroutine,
                'process'   => new Process('cd ' . escapeshellarg(ROOT_PATH) . ' && php forge internal:coroutine ' . $coroutine['hash']),
                'socket'    => fopen($socketPath, 'r'),
                'data'      => null
            ];
        }

        $coroutineWaitingTotal = count($coroutinesWaiting);
        $coroutinesFinished = [];

        $startTime = microtime(true) * 1000;
        while (true) {
            foreach ($coroutinesWaiting as &$coroutineWaiting) {
                if (in_array($coroutineWaiting['coroutine']['hash'], $coroutinesFinished) === false) { //} && file_exists($coroutinePath . sha1($coroutineWaiting['coroutine']['hash'])) === true) {

                    $data = stream_get_contents($coroutineWaiting['socket']);
                    if ($data !== null && $data !== false && $data !== '') {
                        $coroutineWaiting['data'] = $data;
                        fclose($coroutineWaiting['socket']);
                        $coroutinesFinished[] = $coroutineWaiting['coroutine']['hash'];
                    }
                }
            }

            if (count($coroutinesFinished) === $coroutineWaitingTotal) {
                break;
            }

            time_nanosleep(0, 1000000);

            if ($this->timeout > 0 && ((microtime(true) * 1000) - $startTime) > $this->timeout) {
                break;
            }
        }

        foreach ($coroutinesWaiting as &$coroutineWaiting) {

            $responsePath = ($coroutinePath . sha1($coroutineWaiting['coroutine']['hash']));
            if ($coroutineWaiting['data'] === null) {
                foreach ($coroutinesWaiting as $_coroutineWaiting) {
                    unlink($coroutinePath . $_coroutineWaiting['coroutine']['hash']);
                }
                throw new Timeout('Coroutine exceeds maximum execution time of ' . $this->timeout . 'ms.');
            }

            $data = unserialize($coroutineWaiting['data']);
            if ($data === false) {
                $results[] = null;
            } else {
                if ($data['error'] !== null) {
                    foreach ($coroutinesWaiting as &$_coroutineWaiting) {
                        unlink($coroutinePath . $_coroutineWaiting['coroutine']['hash']);
                        if (file_exists($responsePath) === true) {
                            unlink($responsePath);
                        }
                    }
                    $error = (array)json_decode($data['error']);
                    if (Environment::default()->ERROR_HANDLER_ENABLED === true) {
                        $errorHandler = Kernel::getErrorHandler();
                        $errorHandler->handleError($error['type'], $error['message'], $error['file'], $error['line']);
                    }

                    trigger_error('Coroutine Error - ' . $error['message'], E_USER_ERROR);
                }
                if ($echoOutput === true && $data['buffer'] !== '') {
                    echo $data['buffer'];
                }
                $results[] = unserialize($data['response']);
            }
            unlink($coroutinePath . $coroutineWaiting['coroutine']['hash']);
            unlink($responsePath);
        }

        return $results;
    }

    /**
     * Retrieves the name of the calling function or method.
     *
     * @return string|null The name of the calling function or method, or null if not found.
     *
     * @see debug_backtrace()
     */
    private static function __getCaller()
    {
        $lastFunc = null;

        $debugBacktrace = debug_backtrace();
        foreach ($debugBacktrace as $_debugBacktrace) {
            if ($_debugBacktrace['class'] === 'Flames\\Coroutine') {
                continue;
            }
            $lastFunc = $_debugBacktrace['class'];
            break;
        }

        return $lastFunc;
    }
}