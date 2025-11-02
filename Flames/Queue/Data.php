<?php

namespace Flames\Queue;

use Flames\Collection\Arr;

/**
 * @internal
 */
class Data
{
    private const __VERSION__ = 1;

    public static function mountData(string $class) : Arr
    {
        $path = (ROOT_PATH . str_replace('\\', '/', $class) . '.php');

        $basePath  = (ROOT_PATH . '.cache/queue/');
        $cachePath = ($basePath . sha1($class));
        $currentTime = filemtime($path);

        if (file_exists($cachePath) === true) {
            $data = unserialize(file_get_contents($cachePath));
            if ($data->version === self::__VERSION__ && $data->timestamp === $currentTime) {
                return $data;
            }
        }

        $data = self::__getReflection($class);
        $data->timestamp = $currentTime;

        $success = @file_put_contents($cachePath, serialize($data));
        if ($success === false) {
            if (is_dir($basePath) === false) {
                $mask = umask(0);
                mkdir($basePath, 0777, true);
                umask($mask);
                @file_put_contents($cachePath, serialize($data));
            }
        }

        return $data;
    }

    private static function __getReflection(string $class) : Arr
    {
        $data = Arr([
            'version'   => self::__VERSION__,
            'timestamp' => null,
            'class'     => $class,
            'database'  => null,
            'table'     => null,
            'timeout'   => 300,
            'timelimit' => 600,
            'delay'     => 0.1
        ]);

        $reflection = new \ReflectionClass($class);

        // Get database/table
        $attributes = $reflection->getAttributes();
        foreach($attributes as $attribute) {
            $attributeName = $attribute->getName();

            if ($attributeName === \Flames\Orm\Database::class) {
                $arguments = $attribute->getArguments();
                if (isset($arguments['name']))
                    $data->database = $arguments['name'];
            }

            elseif ($attributeName === \Flames\Orm\Table::class) {
                $arguments = $attribute->getArguments();
                if (isset($arguments['name']))
                    $data->table = $arguments['name'];
            }

            elseif ($attributeName === \Flames\Queue\Delay::class) {
                $arguments = $attribute->getArguments();
                if (isset($arguments['delay']))
                    $data->delay = (float)$arguments['delay'];
            }

            elseif ($attributeName === \Flames\Queue\Timeout::class) {
                $arguments = $attribute->getArguments();
                if (isset($arguments['timeout']))
                    $data->timeout = (int)$arguments['timeout'];
            }

            elseif ($attributeName === \Flames\Queue\TimeLimit::class) {
                $arguments = $attribute->getArguments();
                if (isset($arguments['timelimit']))
                    $data->timelimit = (int)$arguments['timelimit'];
            }
        }
        if ($data->table === null) {
            $data->table = str_replace('\\', '_', strtolower(substr($class, 17)));
        }

        return $data;
    }
}
