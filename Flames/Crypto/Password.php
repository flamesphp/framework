<?php
declare(strict_types=1);


namespace Flames\Crypto;

use Flames\Environment;

class Password
{
    public static function toHash(string $password, string|null $key = null)
    {
        if ($key === null) {
            $key = sha1('FLAMES_DEFAULT_SALT_KEY');
        }
        $key = sha1(sha1($key) . '|' . sha1('FLAMES_INTERNAL_SALT_KEY'));

        $appToken    = Environment::get('APP_KEY');
        $cryptoToken = Environment::get('CRYPTO_KEY');

        $password = strrev(sha1($appToken . '|' . $password . '|' . $appToken));
        $password = strrev(sha1($cryptoToken . '|' . $password . '|' . $appToken));
        $password = strrev(sha1($key . '|' . $password . '|' . $key));
        $password = strrev(sha1($appToken . '|' . $cryptoToken . '|' . $key . '|' . $password . '|' . $appToken . '|' . $cryptoToken . '|' . $key . '|'));

        $split = str_split($password, 2);
        $password = '';
        foreach ($split as $_split) {
            $password .= strrev(sha1($key . '|' . hexdec($_split) . '|' . $appToken . '|' . $cryptoToken));
        }
        $password = sha1($key . '|' . $password . '|' . $appToken . '|' . $cryptoToken);
        $passwordMix = sha1($appToken . '|' . $cryptoToken . '|' . $password . '|' . $key);

        $password = (
            substr($passwordMix, 0, 8) . ':' .
            substr($password, 0, 8) . ':' .
            substr($password, 8, 4) .
            substr($password, 12, 4) .
            substr($passwordMix, 8, 4) . ':' .
            substr($password, 16, 4) .
            substr($passwordMix, 12, 4) . ':' .
            substr($passwordMix, 16, 4) . ':' .
            substr($password, 20, 12) . ':' .
            substr($password, 32, 8) . ':' .
            substr($passwordMix, 32, 8) . ':' .
            substr($passwordMix, 20, 12) . ':'
        );

        $passwordPost = sha1($appToken . '|' . $cryptoToken . '|' . $password . '|' . $key);
        $password .= (
            substr($passwordPost, 0, 12) . ':' .
            substr($passwordPost, 12, 26)
        );

        return $password;
    }

    public static function isValidHash(string $hash, string $password, string|null $key = null) : bool
    {
        return ($hash === self::toHash($password, $key));
    }
}