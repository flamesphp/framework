<?php
declare(strict_types=1);


namespace Flames\Framework;

use Flames\Env\Env;
use Flames\Ready\Functions;

class Cache
{
    public static function getPath(): string
    {
        return Functions::once(function() {
            $cachePath = Env::get('CACHE_PATH');

            if (!empty($cachePath) && !str_starts_with($cachePath, '/') && !str_starts_with($cachePath, '~')) {
                $cachePath = ('../' . $cachePath);
            }

            if (empty($cachePath)) {
                $cachePath = sys_get_temp_dir();

                if (empty($cachePath) || !is_dir($cachePath) || !is_writable($cachePath)) {
                    $cachePath = ROOT_PATH;
                } else {
                    $cachePath .= '/';
                }

                $cachePath .= '.cache/';

                if (empty($cachePath) || !is_dir($cachePath) || !is_writable($cachePath)) {
                    self::forceCreateDirectory($cachePath);
                }
            }

            if (!is_dir($cachePath) || !is_writable($cachePath)) {
                self::forceCreateDirectory($cachePath);
            }

            $realCache  = rtrim(realpath($cachePath) ?: $cachePath, '/') . '/';
            $realPublic = rtrim(realpath(ROOT_PATH . 'public') ?: (ROOT_PATH . 'public'), '/') . '/';

            if (str_starts_with($realCache, $realPublic)) {
                $cachePath = ROOT_PATH . '.cache/';
                self::forceCreateDirectory($cachePath);
                $realCache = $cachePath;
            }

            return $realCache;
        });
    }

    protected static function forceCreateDirectory(string $path)
    {
        $mask = umask(0);
        mkdir($path, 0777, true);
        umask($mask);
    }
}