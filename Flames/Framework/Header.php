<?php
declare(strict_types=1);


namespace Flames\Framework;

use Flames\Collection\Arr;

/**
 * Class Header represents a utility class for managing HTTP headers.
 */
final class Header
{
    protected static array $data = [];

    public static function set(string $key, mixed $value): void
    {
        self::$data[$key] = (string) $value;
    }

    public static function get(string $key): mixed
    {
        if (isset(self::$data[$key]) === true) {
            return self::$data[$key];
        }

        return null;
    }

    public static function getAll(): Arr
    {
        return Arr(self::$data);
    }

    public static function clear(): void
    {
        self::$data = [];
    }

    public static function send(): void
    {
        try {
            if (array_key_exists('Code', self::$data) === true) {
                @http_response_code((int) self::$data['Code']);
            } elseif (array_key_exists('code', self::$data) === true) {
                @http_response_code((int) self::$data['code']);
            }

            foreach (self::$data as $key => $value) {
                if (strtolower($key) === 'code') {
                    continue;
                }

                header($key . ':' . $value);
            }
        } catch (\Exception $e) {
            if (\Flames\Forge\Cli::isCli() === true) {
                return;
            }

            throw $e;
        }
    }

    public static function redirect(string $url, bool $sendNow = false): Arr|string
    {
        if ($sendNow === true) {
            header('Location: ' . $url);
            exit;
        }

        return Arr(['flames.redirect' => $url]);
    }
}
