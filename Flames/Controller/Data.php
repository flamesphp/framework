<?php

namespace Flames\Controller;

use Flames\Collection\Arr;
use Flames\Client\View;

/**
 * @internal
 */
class Data
{
    private const __VERSION__ = 1;

    public static function mountData(string $class) : Arr
    {
        $path = (ROOT_PATH . str_replace('\\', '/', $class) . '.php');

        $basePath  = (ROOT_PATH . '.cache/.flames/controller/');
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

    private static function __getReflection(string $class) : Arr
    {
        $data = Arr([
            'version'  => self::__VERSION__,
            'class'    => $class,
            'methods'  => Arr()
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

                if ($attributeName === View::class) {
                    $arguments = $attribute->getArguments();
                    if (isset($arguments['path'])) {
                        $data->methods[$method->name] = Arr([
                            'name' => $method->name,
                            'path' => $arguments['path']
                        ]);
                    }
                }
            }
        }

        return $data;
    }
}
