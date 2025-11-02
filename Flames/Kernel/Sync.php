<?php

namespace Flames\Kernel;

use Flames\Kernel;

/**
 * @internal
 */
class Sync
{
    public static function getData(string $key)
    {
        $basePath = (ROOT_PATH . '.cache/sync/' . sha1($key));

        try {
            $diffTs = (microtime(true) - filemtime($basePath));
            if ($diffTs > 86400) { // 1 day cache
                return self::syncData($basePath, $key);
            }
            return file_get_contents($basePath);
        } catch (\Exception $e) {
            return self::syncData($basePath, $key);
        }
    }

    protected static function syncData(string $basePath, string $key)
    {
        $remotePath = ('https://cdn.jsdelivr.net/gh/flamesphp/cdn@' . Kernel::CDN_VERSION . '/sync/' . $key . '.blob');
        $data = file_get_contents($remotePath);

        $success = false;
        try {
            $success = file_put_contents($basePath, $data);
        } catch (\Exception $e) {}

        if ($success === false) {
            $mask = umask(0);
            mkdir(ROOT_PATH . '.cache/sync/', 0777, true);
            umask($mask);
            file_put_contents($basePath, $data);
        }

        return $data;
    }
}