<?php


namespace Flames\Client;

use Flames\Client\UserAgentParser;
use Flames\Collection\Strings;
use Flames\Js;
use Flames\Kernel;

/**
 * The Plaform class provides methods for determining the operating system platform.
 */
class Platform
{
    const UNKNOWN = 'Unknown';
    const MACINTOSH = 'Macintosh';
    const CHROME_OS = 'Chrome OS';
    const LINUX = 'Linux';
    const WINDOWS = 'Windows';
    const ANDROID = 'Android';
    const BLACKBERRY = 'BlackBerry';
    const FREEBSD = 'FreeBSD';
    const IPAD = 'iPad';
    const IPHONE = 'iPhone';
    const IPOD = 'iPod';
    const KINDLE = 'Kindle';
    const KINDLE_FIRE = 'Kindle Fire';
    const NETBSD = 'NetBSD';
    const NEW_NINTENDO_3DS = 'New Nintendo 3DS';
    const NINTENDO_3DS = 'Nintendo 3DS';
    const NINTENDO_DS = 'Nintendo DS';
    const NINTENDO_SWITCH = 'Nintendo Switch';
    const NINTENDO_WII = 'Nintendo Wii';
    const NINTENDO_WIIU = 'Nintendo WiiU';
    const OPENBSD = 'OpenBSD';
    const PLAYBOOK = 'PlayBook';
    const PLAYSTATION_3 = 'PlayStation 3';
    const PLAYSTATION_4 = 'PlayStation 4';
    const PLAYSTATION_5 = 'PlayStation 5';
    const PLAYSTATION_VITA = 'PlayStation Vita';
    const SAILFISH = 'Sailfish';
    const SYMBIAN = 'Symbian';
    const TIZEN = 'Tizen';
    const WINDOWS_PHONE = 'Windows Phone';
    const XBOX = 'Xbox';
    const XBOX_ONE = 'Xbox One';

    protected static object|null $nativeInfo = null;

    /**
     * Returns the name of the platform.
     *
     * @return string The name of the platform.
     */
    public static function getName(): string
    {
        $userAgentParser = (new UserAgentParser())->parse();
        return $userAgentParser['platform'];
    }

    public static function isWindows(): bool
    {
        $name = self::getName();

        return (
            $name === self::WINDOWS ||
            $name === self::WINDOWS_PHONE ||
            $name === self::XBOX ||
            $name === self::XBOX_ONE
        );
    }

    public static function isLinux(): bool
    {
        return (self::getName() === self::LINUX);
    }

    public static function isUnix(): bool
    {
        $name = self::getName();

        return (
            $name === self::MACINTOSH ||
            $name === self::CHROME_OS ||
            $name === self::LINUX ||
            $name === self::ANDROID ||
            $name === self::FREEBSD ||
            $name === self::IPAD ||
            $name === self::IPHONE ||
            $name === self::IPOD ||
            $name === self::KINDLE ||
            $name === self::KINDLE_FIRE ||
            $name === self::NETBSD ||
            $name === self::OPENBSD ||
            $name === self::PLAYSTATION_3 ||
            $name === self::PLAYSTATION_4 ||
            $name === self::PLAYSTATION_5 ||
            $name === self::PLAYSTATION_VITA
        );
    }

    public static function isIos(): bool
    {
        $name = self::getName();

        return (
            $name === self::MACINTOSH ||
            $name === self::IPAD ||
            $name === self::IPHONE ||
            $name === self::IPOD
        );
    }

    public static function isDarwin(): bool
    {
        return (self::getName() === self::MACINTOSH);
    }

    public static function isMacintosh(): bool
    {
        return self::isDarwin();
    }

    public static function isAndroid(): bool
    {
        $name = self::getName();

        return (
            $name === self::CHROME_OS ||
            $name === self::ANDROID ||
            $name === self::KINDLE ||
            $name === self::KINDLE_FIRE
        );
    }

    public static function isBsd(): bool
    {
        $name = self::getName();

        return (
            $name === self::FREEBSD ||
            $name === self::NETBSD ||
            $name === self::OPENBSD
        );
    }

    public static function isMobile(): bool
    {
        $name = self::getName();

        return (
            $name === self::ANDROID ||
            $name === self::BLACKBERRY ||
            $name === self::IPAD ||
            $name === self::IPHONE ||
            $name === self::IPOD ||
            $name === self::KINDLE ||
            $name === self::KINDLE_FIRE ||
            $name === self::NEW_NINTENDO_3DS ||
            $name === self::NINTENDO_3DS ||
            $name === self::NINTENDO_DS ||
            $name === self::PLAYSTATION_VITA ||
            $name === self::SYMBIAN ||
            $name === self::WINDOWS_PHONE
        );
    }

    public static function isConsole(): bool
    {
        $name = self::getName();

        return (
            $name === self::NEW_NINTENDO_3DS ||
            $name === self::NINTENDO_3DS ||
            $name === self::NINTENDO_DS ||
            $name === self::NINTENDO_SWITCH ||
            $name === self::NINTENDO_WII ||
            $name === self::NINTENDO_WIIU ||
            $name === self::PLAYSTATION_3 ||
            $name === self::PLAYSTATION_4 ||
            $name === self::PLAYSTATION_5 ||
            $name === self::PLAYSTATION_VITA ||
            $name === self::XBOX ||
            $name === self::XBOX_ONE
        );
    }

    public static function isPlaystation(): bool
    {
        $name = self::getName();

        return (
            $name === self::PLAYSTATION_3 ||
            $name === self::PLAYSTATION_4 ||
            $name === self::PLAYSTATION_5 ||
            $name === self::PLAYSTATION_VITA
        );
    }

    public static function isXbox(): bool
    {
        $name = self::getName();

        return (
            $name === self::XBOX ||
            $name === self::XBOX_ONE ||
            $name === self::PLAYSTATION_5 ||
            $name === self::PLAYSTATION_VITA
        );
    }

    public static function isNintendo(): bool
    {
        $name = self::getName();

        return (
            $name === self::NEW_NINTENDO_3DS ||
            $name === self::NINTENDO_3DS ||
            $name === self::NINTENDO_DS ||
            $name === self::NINTENDO_SWITCH ||
            $name === self::NINTENDO_WII ||
            $name === self::NINTENDO_WIIU
        );
    }

    public static function getArch(): ?string
    {
        if (self::isNativeBuild() !== true) {
            return null;
        }

        $nativeInfo = self::getNativeInfo();
        return Strings::toLower($nativeInfo->arch);
    }

    public static function getVersion(): ?string
    {
        if (self::isNativeBuild() !== true) {
            return null;
        }

        $nativeInfo = self::getNativeInfo();
        return $nativeInfo->version;
    }

    protected static function isNativeBuild(): bool
    { return Kernel::isNativeBuild(); }

    protected static function getNativeInfo(): object|null
    {
        if (self::$nativeInfo !== null) {
            return self::$nativeInfo;
        }

        $window = Js::getWindow();
        $nativeInfo = (string)$window->Flames->__nativeInfo__;

        $nativeInfo = json_decode(base64_decode($nativeInfo));
        if ($nativeInfo === false) {
            return null;
        }

        self::$nativeInfo = $nativeInfo;
        return self::$nativeInfo;
    }
}



