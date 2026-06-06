<?php
declare(strict_types=1);


namespace Flames\Connection;


class HttpClient
{
    public static function isHttpClient(): bool
    {
        $userAgent = self::getUserAgent();
        return (
            str_contains($userAgent, 'postman') === true ||
            str_contains($userAgent, 'swagger') === true ||
            str_contains($userAgent, 'insomnia') === true ||
            str_contains($userAgent, 'jetbrains') === true ||
            str_contains($userAgent, 'thunder') === true ||
            str_contains($userAgent, 'katalon') === true ||
            str_contains($userAgent, 'httppie') === true ||
            str_contains($userAgent, 'pet') === true ||
            str_contains($userAgent, 'hoppscotch') === true ||
            str_contains($userAgent, 'jmeter') === true ||
            str_contains($userAgent, 'soap') === true ||
            str_contains($userAgent, 'sigma') === true ||
            str_contains($userAgent, 'ready') === true ||
            str_contains($userAgent, 'assertible') === true ||
            str_contains($userAgent, 'paw') === true ||
            str_contains($userAgent, 'karate') === true ||
            str_contains($userAgent, 'tricentis') === true ||
            str_contains($userAgent, 'ninja') === true ||
            str_contains($userAgent, 'apigee') === true
        );
    }

    public static function isPostman(): bool
    {
        return (str_contains(self::getUserAgent(), 'postman') === true);
    }

    public static function isSwagger(): bool
    {
        return (str_contains(self::getUserAgent(), 'swagger') === true);
    }

    public static function isInsomnia(): bool
    {
        return (str_contains(self::getUserAgent(), 'insomnia') === true);
    }

    public static function isJetBrains(): bool
    {
        return (str_contains(self::getUserAgent(), 'jetbrains') === true);
    }

    public static function isThunderClient(): bool
    {
        return (str_contains(self::getUserAgent(), 'thunder') === true);
    }

    public static function isKatalon(): bool
    {
        return (str_contains(self::getUserAgent(), 'katalon') === true);
    }

    public static function isHttpPie(): bool
    {
        return (str_contains(self::getUserAgent(), 'httppie') === true);
    }

    public static function isPetApi(): bool
    {
        return (str_contains(self::getUserAgent(), 'pet') === true);
    }

    public static function isHoppscotch(): bool
    {
        return (str_contains(self::getUserAgent(), 'hoppscotch') === true);
    }

    public static function isJmeter(): bool
    {
        return (str_contains(self::getUserAgent(), 'jmeter') === true);
    }

    public static function isSoapUi(): bool
    {
        return (str_contains(self::getUserAgent(), 'soap') === true);
    }

    public static function isTestSigma(): bool
    {
        return (str_contains(self::getUserAgent(), 'sigma') === true);
    }

    public static function isReadyApi(): bool
    {
        return (str_contains(self::getUserAgent(), 'ready') === true);
    }

    public static function isAssertible(): bool
    {
        return (str_contains(self::getUserAgent(), 'assertible') === true);
    }

    public static function isPaw(): bool
    {
        return (str_contains(self::getUserAgent(), 'paw') === true);
    }

    public static function isKarateLabs(): bool
    {
        return (str_contains(self::getUserAgent(), 'karate') === true);
    }

    public static function isTricentisTosca(): bool
    {
        return (str_contains(self::getUserAgent(), 'tricentis') === true);
    }

    public static function isLoadNinja(): bool
    {
        return (str_contains(self::getUserAgent(), 'ninja') === true);
    }

    public static function isApiGee(): bool
    {
        return (str_contains(self::getUserAgent(), 'apigee') === true);
    }
    
    private static function getUserAgent(): string
    {
        if (isset($_SERVER['HTTP_USER_AGENT']) === false) {
            return '';
        }
        return strtolower($_SERVER['HTTP_USER_AGENT']);
    }
}