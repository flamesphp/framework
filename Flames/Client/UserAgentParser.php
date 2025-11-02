<?php

namespace Flames\Client;

use Flames\Js;
use Flames\Kernel;

class UserAgentParser
{
    const PLATFORM        = 'platform';
    const BROWSER         = 'browser';
    const BROWSER_VERSION = 'version';

    public static function getRaw(): ?string
    {
        $userAgent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null);
        if ($userAgent === '') {
            $userAgent = null;
        }

        if (Kernel::MODULE !== 'SERVER') {
            $userAgent = Js::getWindow()->navigator->userAgent;
            if ($userAgent === '') {
                $userAgent = null;
            }
        }

        return $userAgent;
    }

    protected function parseUserAgent($u_agent = null)
    {
        if( $u_agent === null && isset($_SERVER['HTTP_USER_AGENT']) ) {
            $u_agent = (string)$_SERVER['HTTP_USER_AGENT'];
        }

        if( $u_agent === null ) {
            throw new \InvalidArgumentException('parse_user_agent requires a user agent');
        }

        $platform = null;
        $browser  = null;
        $version  = null;

        $return = [ self::PLATFORM => &$platform, self::BROWSER => &$browser, self::BROWSER_VERSION => &$version ];

        if( !$u_agent ) {
            return $return;
        }

        if( preg_match('/\((.*?)\)/m', $u_agent, $parent_matches) ) {
            preg_match_all(<<<'REGEX'
/(?P<platform>BB\d+;|Android|Adr|Symbian|Sailfish|CrOS|Tizen|iPhone|iPad|iPod|Linux|(?:Open|Net|Free)BSD|Macintosh|
Windows(?:\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(?:New\ )?Nintendo\ (?:WiiU?|3?DS|Switch)|Xbox(?:\ One)?)
(?:\ [^;]*)?
(?:;|$)/imx
REGEX
                , $parent_matches[1], $result);

            $priority = [ 'Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11', 'Sailfish' ];

            $result[self::PLATFORM] = array_unique($result[self::PLATFORM]);
            if( count($result[self::PLATFORM]) > 1 ) {
                if( $keys = array_intersect($priority, $result[self::PLATFORM]) ) {
                    $platform = reset($keys);
                } else {
                    $platform = $result[self::PLATFORM][0];
                }
            } elseif( isset($result[self::PLATFORM][0]) ) {
                $platform = $result[self::PLATFORM][0];
            }
        }

        if( $platform == 'linux-gnu' || $platform == 'X11' ) {
            $platform = 'Linux';
        } elseif( $platform == 'CrOS' ) {
            $platform = 'Chrome OS';
        } elseif( $platform == 'Adr' ) {
            $platform = 'Android';
        } elseif( $platform === null ) {
            if( preg_match_all('%(?P<platform>Android)[:/ ]%ix', $u_agent, $result) ) {
                $platform = $result[self::PLATFORM][0];
            }
        }

        preg_match_all(<<<'REGEX'
%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|
TizenBrowser|(?:Headless)?Chrome|YaBrowser|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|(?-i:Edge)|EdgA?|CriOS|UCBrowser|Puffin|
OculusBrowser|SamsungBrowser|SailfishBrowser|XiaoMi/MiuiBrowser|YaApp_Android|Whale|
Baiduspider|Applebot|Facebot|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|ChatGPT-User|GPTBot|OAI-SearchBot|
Valve\ Steam\ Tenfoot|Mastodon|
NintendoBrowser|PLAYSTATION\ (?:\d|Vita)+)
\)?;?
(?:[:/ ](?P<version>[0-9A-Z.]+)|/[A-Z]*)
%ix
REGEX
            , $u_agent, $result);

        // If nothing matched, return null (to avoid undefined index errors)
        if( !isset($result[self::BROWSER][0], $result[self::BROWSER_VERSION][0]) ) {
            if( preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)([/ :](?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result) ) {
                return [ self::PLATFORM => $platform ?: null, self::BROWSER => $result[self::BROWSER], self::BROWSER_VERSION => empty($result[self::BROWSER_VERSION]) ? null : $result[self::BROWSER_VERSION] ];
            }

            return $return;
        }

        if( preg_match('/rv:(?P<version>[0-9A-Z.]+)/i', $u_agent, $rv_result) ) {
            $rv_result = $rv_result[self::BROWSER_VERSION];
        }

        $browser = $result[self::BROWSER][0];
        $version = $result[self::BROWSER_VERSION][0];

        $lowerBrowser = array_map('strtolower', $result[self::BROWSER]);

        $find = function( $search, &$key = null, &$value = null ) use ( $lowerBrowser ) {
            $search = (array)$search;

            foreach( $search as $val ) {
                $xkey = array_search(strtolower($val), $lowerBrowser);
                if( $xkey !== false ) {
                    $value = $val;
                    $key   = $xkey;

                    return true;
                }
            }

            return false;
        };

        $findT = function( array $search, &$key = null, &$value = null ) use ( $find ) {
            $value2 = null;
            if( $find(array_keys($search), $key, $value2) ) {
                $value = $search[$value2];

                return true;
            }

            return false;
        };

        $key = 0;
        $val = '';
        if( $findT([ 'OPR' => 'Opera', 'Facebot' => 'iMessageBot', 'UCBrowser' => 'UC Browser', 'YaBrowser' => 'Yandex', 'YaApp_Android' => 'Yandex', 'Iceweasel' => 'Firefox', 'Icecat' => 'Firefox', 'CriOS' => 'Chrome', 'Edg' => 'Edge', 'EdgA' => 'Edge', 'XiaoMi/MiuiBrowser' => 'MiuiBrowser' ], $key, $browser) ) {
            $version = is_numeric(substr($result[self::BROWSER_VERSION][$key], 0, 1)) ? $result[self::BROWSER_VERSION][$key] : null;
        } elseif( $find('Playstation Vita', $key, $platform) ) {
            $platform = 'PlayStation Vita';
            $browser  = 'Browser';
        } elseif( $find([ 'Kindle Fire', 'Silk' ], $key, $val) ) {
            $browser  = $val == 'Silk' ? 'Silk' : 'Kindle';
            $platform = 'Kindle Fire';
            if( !($version = $result[self::BROWSER_VERSION][$key]) || !is_numeric($version[0]) ) {
                $version = $result[self::BROWSER_VERSION][array_search('Version', $result[self::BROWSER])];
            }
        } elseif( $find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS' ) {
            $browser = 'NintendoBrowser';
            $version = $result[self::BROWSER_VERSION][$key];
        } elseif( $find([ 'Kindle' ], $key, $platform) ) {
            $browser = $result[self::BROWSER][$key];
            $version = $result[self::BROWSER_VERSION][$key];
        } elseif( $find('Opera', $key, $browser) ) {
            $find('Version', $key);
            $version = $result[self::BROWSER_VERSION][$key];
        } elseif( $find('Puffin', $key, $browser) ) {
            $version = $result[self::BROWSER_VERSION][$key];
            if( strlen($version) > 3 ) {
                $part = substr($version, -2);
                if( ctype_upper($part) ) {
                    $version = substr($version, 0, -2);

                    $flags = [ 'IP' => 'iPhone', 'IT' => 'iPad', 'AP' => 'Android', 'AT' => 'Android', 'WP' => 'Windows Phone', 'WT' => 'Windows' ];
                    if( isset($flags[$part]) ) {
                        $platform = $flags[$part];
                    }
                }
            }
        } elseif( $find([ 'Googlebot', 'Applebot', 'IEMobile', 'Edge', 'Midori', 'Whale', 'Vivaldi', 'OculusBrowser', 'SamsungBrowser', 'Valve Steam Tenfoot', 'Chrome', 'HeadlessChrome', 'SailfishBrowser' ], $key, $browser) ) {
            $version = $result[self::BROWSER_VERSION][$key];
        } elseif( $rv_result && $find('Trident') ) {
            $browser = 'MSIE';
            $version = $rv_result;
        } elseif( $browser == 'AppleWebKit' ) {
            if( $platform == 'Android' ) {
                $browser = 'Android Browser';
            } elseif( strpos((string)$platform, 'BB') === 0 ) {
                $browser  = 'BlackBerry Browser';
                $platform = 'BlackBerry';
            } elseif( $platform == 'BlackBerry' || $platform == 'PlayBook' ) {
                $browser = 'BlackBerry Browser';
            } elseif( $find('Safari', $key, $browser) || $find('TizenBrowser', $key, $browser) ) {
                $version = $result[self::BROWSER_VERSION][$key];
            } elseif( count($result[self::BROWSER]) ) {
                $key     = count($result[self::BROWSER]) - 1;
                $browser = $result[self::BROWSER][$key];
                $version = $result[self::BROWSER_VERSION][$key];
            }

            if( $find('Version', $key) ) {
                $version = $result[self::BROWSER_VERSION][$key];
            }
        } elseif( $pKey = preg_grep('/playstation \d/i', $result[self::BROWSER]) ) {
            $pKey = reset($pKey);

            $platform = 'PlayStation ' . preg_replace('/\D/', '', $pKey);
            $browser  = 'NetFront';
        }

        return $return;
    }

    protected static $cache = [];
    public function parse($u_agent = null)
    {
        if (isset(self::$cache[$u_agent]) === true) {
            return self::$cache[$u_agent];
        }
        if (Kernel::MODULE === 'SERVER') {
            $parsed = $this->parseUserAgent($u_agent);
        } else {
            $u_agent = Js::getWindow()->navigator->userAgent;
            $parsed = $this->parseUserAgent($u_agent);
        }

        self::$cache[$u_agent] = $parsed;
        return self::$cache[$u_agent];
    }

    public function __invoke($u_agent = null)
    {
        return $this->parse($u_agent);
    }

}
