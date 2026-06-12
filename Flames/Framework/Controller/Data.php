<?php
declare(strict_types=1);


namespace Flames\Framework\Controller;

use Flames\Collection\Arr;
use Flames\Framework\Cache;

/**
 * @internal
 */
class Data
{
    private const int __VERSION__ = 1;

    public static function getViewPath(string $class, string $method): ?string
    {
        $data = self::mountData($class);

        if ($data->methods->containsKey($method) === false) {
            return null;
        }

        return $data->methods[$method]->path;
    }

    public static function mountData(string $class): Arr
    {
        $path = (ROOT_PATH . str_replace('\\', '/', $class) . '.php');

        $basePath = rtrim(Cache::getPath(), '/') . '/flames/framework-controller/';
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

    private static function __getReflection(string $class): Arr
    {
        $data = Arr([
            'version' => self::__VERSION__,
            'class' => $class,
            'methods' => Arr(),
        ]);

        $reflection = new \ReflectionClass($class);
        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes() as $attribute) {
                if ($attribute->getName() !== View::class) {
                    continue;
                }

                $instance = $attribute->newInstance();
                $data->methods[$method->name] = Arr([
                    'name' => $method->name,
                    'path' => $instance->path,
                ]);
            }
        }

        return $data;
    }
}
