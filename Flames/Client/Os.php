<?php

namespace Flames\Client;

use Flames\Js;
use Flames\Kernel;

class Os
{
    const WINDOWS = 'Windows';
    const LINUX = 'Linux';
    const ANDROID = 'Android';
    const BSD = 'BSD';
    const IOS = 'iOS';
    const NINTENDO = 'Nintento';
    const PLAYSTATION = 'Playstation';
    const XBOX = 'Xbox';

    protected static object|null $nativeInfo = null;

    public static function getName(): string
    {
        $platform = Platform::getName();

        if (
            $platform === Platform::WINDOWS ||
            $platform === Platform::WINDOWS_PHONE

        ) {
            return self::WINDOWS;
        }
        elseif (
            $platform === Platform::ANDROID ||
            $platform === Platform::CHROME_OS ||
            $platform === Platform::KINDLE ||
            $platform === Platform::KINDLE_FIRE

        ) {
            return self::ANDROID;
        }
        elseif (
            $platform === Platform::MACINTOSH ||
            $platform === Platform::IPAD ||
            $platform === Platform::IPHONE ||
            $platform === Platform::IPOD

        ) {
            return self::IOS;
        }
        elseif (
            $platform === Platform::FREEBSD ||
            $platform === Platform::NETBSD ||
            $platform === Platform::OPENBSD

        ) {
            return self::BSD;
        }
        elseif ($platform === Platform::LINUX) {
            return self::LINUX;
        }
        elseif (
            $platform === Platform::NEW_NINTENDO_3DS ||
            $platform === Platform::NINTENDO_3DS ||
            $platform === Platform::NINTENDO_DS ||
            $platform === Platform::NINTENDO_SWITCH ||
            $platform === Platform::NINTENDO_WII ||
            $platform === Platform::NINTENDO_WIIU

        ) {
            return self::NINTENDO;
        }
        elseif (
            $platform === Platform::PLAYSTATION_3 ||
            $platform === Platform::PLAYSTATION_4 ||
            $platform === Platform::PLAYSTATION_5 ||
            $platform === Platform::PLAYSTATION_VITA

        ) {
            return self::PLAYSTATION;
        }
        elseif (
            $platform === Platform::XBOX ||
            $platform === Platform::XBOX_ONE

        ) {
            return self::XBOX;
        }

        return $platform;
    }

    public static function getVersion(): ?string
    {
        if (self::isNativeBuild() !== true) {
            return null;
        }

        $nativeInfo = self::getNativeInfo();
        return $nativeInfo->version;
    }

    public static function getReleaseVersion(): ?string
    {
        if (self::isNativeBuild() !== true) {
            return null;
        }

        $nativeInfo = self::getNativeInfo();
        return $nativeInfo->release;
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