<?php
declare(strict_types=1);


namespace Flames\Client;

use Flames\Collection\Arr;

class Browser
{
    public static function getName(bool $includeVersion = false): string|Arr
    {
        $userAgentParser = (new UserAgentParser())->parse();

        if ($includeVersion === true) {
            return Arr([
                'browser' => $userAgentParser['browser'],
                'version' => $userAgentParser['browserVersion']
            ]);
        }

        return $userAgentParser['browser'];
    }

    public static function getVersion()
    {
        $userAgentParser = (new UserAgentParser())->parse();
        return $userAgentParser['browserVersion'];
    }

}