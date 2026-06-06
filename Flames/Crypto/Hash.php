<?php
declare(strict_types=1);


namespace Flames\Crypto;

use Flames\Collection\Strings;
use Flames\Environment;

class Hash
{
    public static function getRandom(int $length = 40)
    {
        $hash = '';
        while (strlen($hash) < $length) {
            $hash .= sha1(Strings::getRandom(1024));
        }

        return substr($hash, 0, $length);
    }
}