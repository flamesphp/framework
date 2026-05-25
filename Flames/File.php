<?php

namespace Flames;

class File
{
    /**
     * Checks if a file or directory exists.
     *
     * @param string $path The path to the file or directory.
     * @return bool Returns TRUE if the file or directory exists, FALSE otherwise.
     */
    public static function exists(string $path) : bool
    {
        return file_exists($path);
    }

    /**
     * Retrieves the MIME type of a file based on its path.
     *
     * @param string $path The path to the file.
     * @return string|null The MIME type of the file, or null if file not exists.
     */
    public static function getMimeType(string $path) : string|null
    {
        try  {
            return mime_content_type($path);
        } catch (\Error $e) {
            return null;
        }
    }

    public static function putContents(string $path, string $contents): bool
    {
        $success = false;
        try {
            $success = file_put_contents($path, $contents);
        } catch (\Exception $_) {}

        if ($success === false) {
            $dirPath = dirname($path);

            if (is_dir($dirPath) === false) {
                $mask = umask(0);
                mkdir($dirPath, 0777, true);
                umask($mask);
            }

            $success = file_put_contents($path, $contents);
            if ($success === false) {
                return false;
            }
        }

        return true;
    }

    public static function getContents(string $path): mixed
    {
        return file_get_contents($path);
    }

    public static function delete(string $path): bool
    {
        try {
            return unlink($path);
        } catch (\Exception $_) {}

        if (file_exists($path) === false) {
            return true;
        }

        return false;
    }

    public static function move(string $oldPath, string $newPath): bool
    {
        try {
            return rename($oldPath, $newPath);
        } catch (\Exception $_) {}

        if (file_exists($newPath) === true) {
            return true;
        }

        return false;
    }

    public static function copy(string $fromPath, string $toPath): bool
    {
        try {
            return copy($fromPath, $toPath);
        } catch (\Exception $_) {}

        if (file_exists($toPath) === true) {
            return true;
        }

        return false;
    }
}