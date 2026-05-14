<?php

namespace Flames\Cli\Command\Build\Assets;

use Flames\Collection\Arr;
use Flames\Client\Event;

/**
 * Class Data
 *
 * This class is responsible for mounting data for the given class.
 */
class Data
{
    private const __VERSION__ = 5;

    /**
     * Mounts data for the given class.
     *
     * @param string $class The class name.
     * @return Arr The mounted data.
     */
    public static function mountData(string $class) : Arr
    {
        $path = (ROOT_PATH . str_replace('\\', '/', $class) . '.php');

        $basePath  = (ROOT_PATH . '.cache/.flames/client-controller/');
        $cachePath = ($basePath . sha1($class));
        $currentTime = filemtime($path);

        if (file_exists($cachePath) === true && filemtime($cachePath) === $currentTime) {
            $data = unserialize(file_get_contents($cachePath));
            if ($data->version === self::__VERSION__) {
                return $data;
            }
        }

        $data = self::__getReflection($class);
        $success = @file_put_contents($cachePath, serialize($data));
        if ($success === false) {
            if (is_dir($basePath) === false) {
                $mask = umask(0);
                mkdir($basePath, 0777, true);
                umask($mask);
                @file_put_contents($cachePath, serialize($data));
            }
        }
        @touch($cachePath, $currentTime);
        return $data;
    }

    /**
     * Retrieves reflection data for the given class.
     *
     * @param string $class The class name.
     * @return Arr The reflection data.
     */
    private static function __getReflection(string $class) : Arr
    {
        $data = Arr([
            'version'         => self::__VERSION__,
            'class'           => $class,
            'methods'         => Arr(),
            'staticConstruct' => (method_exists($class, '__constructStatic') === true)
        ]);

        $reflection = new \ReflectionClass($class);

        $methods = $reflection->getMethods();
        foreach ($methods as $method) {
            if ($method->name === 'success' || $method->name === 'error' || $method->name === '__constructStatic') {
                continue;
            }

            $attributes = $method->getAttributes();
            foreach($attributes as $attribute) {
                $attributeName = $attribute->getName();

                if ($attributeName === Event\Click::class) {
                    $arguments = $attribute->getArguments();
                    if (isset($arguments['uid'])) {
                        $data->methods[$method->name] = Arr([
                            'name' => $method->name,
                            'uid' => $arguments['uid'],
                            'type' => 'click'
                        ]);
                    }
                }
                elseif ($attributeName === Event\Change::class) {
                    $arguments = $attribute->getArguments();
                    if (isset($arguments['uid'])) {
                        $data->methods[$method->name] = Arr([
                            'name' => $method->name,
                            'uid' => $arguments['uid'],
                            'type' => 'change'
                        ]);
                    }
                }
                elseif ($attributeName === Event\Input::class) {
                    $arguments = $attribute->getArguments();
                    if (isset($arguments['uid'])) {
                        $data->methods[$method->name] = Arr([
                            'name' => $method->name,
                            'uid' => $arguments['uid'],
                            'type' => 'input'
                        ]);
                    }
                }
            }
        }

        return $data;
    }
}
