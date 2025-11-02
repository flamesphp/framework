<?php

namespace Flames;

use Flames\Kernel\Sync;

class Email
{
    protected static ?array $disposableDomains = null;

    public static function isAddressValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function isAddressRewrite(string $email): bool
    {
        return str_contains($email, '+');
    }

    public static function isAddressDisposable(string $email): bool
    {
        $domain = mb_strtolower(explode('@', trim($email))[1]);
        $domains = self::getDisposableDomains();
        return in_array($domain, $domains);
    }

    public static function isAddressDnsValid(string $email): bool
    {
        $domain = mb_strtolower(explode('@', trim($email))[1]);

        $basePath = (ROOT_PATH . '.cache/sync/email/' . sha1($domain));
        if (file_exists($basePath) === true) {
            return true;
        }

        $checkDns = checkdnsrr($domain, 'MX');
        if ($checkDns === true) {
            try {
                file_put_contents($basePath, '');
                return $checkDns;
            } catch (\Exception $e) {
                $mask = umask(0);
                mkdir(ROOT_PATH . '.cache/sync/email/', 0777, true);
                umask($mask);
                file_put_contents($basePath, '');
                return $checkDns;
            }
        }

        return $checkDns;
    }

    protected static function getDisposableDomains(): array
    {
        if (self::$disposableDomains !== null) {
            return self::$disposableDomains;
        }

        $disposableEmailUri = Sync::getData('domain.disposable.email');
        self::$disposableDomains = self::getDisposableDomainsData($disposableEmailUri);
        return self::$disposableDomains;
    }

    public static function getDisposableDomainsData(string $disposableEmailUri)
    {
        $basePath = (ROOT_PATH . '.cache/sync/email/25fcf48d4316bbba30bb7c391a79714a11858f1');

        try {
            $diffTs = (microtime(true) - filemtime($basePath));
            if ($diffTs > 86400) { // 1 day cache
                return self::syncDisposableDomainsData($basePath, $disposableEmailUri);
            }
            return file($basePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        } catch (\Exception $e) {
            return self::syncDisposableDomainsData($basePath, $disposableEmailUri);
        }
    }

    protected static function syncDisposableDomainsData(string $basePath, $disposableEmailUri)
    {
        $data = file_get_contents($disposableEmailUri);
        try {
            file_put_contents($basePath, $data);
            return file($basePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        } catch (\Exception $e) {
            $mask = umask(0);
            mkdir(ROOT_PATH . '.cache/sync/email/', 0777, true);
            umask($mask);
            file_put_contents($basePath, $data);
        }

        return file($basePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }


}