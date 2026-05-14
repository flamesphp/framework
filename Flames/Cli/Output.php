<?php

namespace Flames\Cli;

/**
 * @internal
 */
final class Output
{
    const RESET   = "\033[0m";
    const BOLD    = "\033[1m";
    const DIM     = "\033[2m";
    const GREEN   = "\033[32m";
    const YELLOW  = "\033[33m";
    const BLUE    = "\033[34m";
    const CYAN    = "\033[36m";
    const WHITE   = "\033[97m";
    const GRAY    = "\033[90m";
    const RED     = "\033[31m";
    const ORANGE  = "\033[38;5;208m";

    public static function line(string $text = ''): void
    {
        self::echo($text . "\n");
    }

    public static function blank(): void
    {
        self::echo("\n");
    }

    public static function success(string $text): void
    {
        self::echo(self::GREEN . self::BOLD . '  ✔  ' . self::RESET . self::GREEN . $text . self::RESET . "\n");
    }

    public static function error(string $text): void
    {
        self::echo(self::RED . self::BOLD . '  ✘  ' . self::RESET . self::RED . $text . self::RESET . "\n");
    }

    public static function info(string $text): void
    {
        self::echo(self::CYAN . '  ℹ  ' . self::RESET . $text . "\n");
    }

    public static function warning(string $text): void
    {
        self::echo(self::YELLOW . self::BOLD . '  ⚠  ' . self::RESET . self::YELLOW . $text . self::RESET . "\n");
    }

    public static function section(string $title): void
    {
        self::echo("\n" . self::YELLOW . self::BOLD . '  ' . strtoupper($title) . self::RESET . "\n");
    }

    /**
     * Prints a command row with aligned columns.
     * $command is rendered in green, $description in gray.
     */
    public static function command(string $command, string $description = '', int $width = 42): void
    {
        $pad = max(1, $width - strlen($command));
        echo '    ' . self::GREEN . $command . self::RESET
            . str_repeat(' ', $pad)
            . self::GRAY . $description . self::RESET . "\n";
    }

    public static function logo(): void
    {
        self::echo(self::ORANGE . self::BOLD . '
    ███████╗██╗      █████╗ ███╗   ███╗███████╗███████╗
    ██╔════╝██║     ██╔══██╗████╗ ████║██╔════╝██╔════╝
    █████╗  ██║     ███████║██╔████╔██║█████╗  ███████╗
    ██╔══╝  ██║     ██╔══██║██║╚██╔╝██║██╔══╝  ╚════██║
    ██║     ███████╗██║  ██║██║ ╚═╝ ██║███████╗███████║
    ╚═╝     ╚══════╝╚═╝  ╚═╝╚═╝     ╚═╝╚══════╝╚══════╝
' . self::RESET);

        self::echo(self::GRAY
            . "    Created by Gabriel 'Kazz' Morgado\n"
            . "    https://github.com/flamesphp/flames\n"
            . self::RESET);

        self::echo(self::GRAY . '    ' . str_repeat('─', 60) . self::RESET . "\n");
    }

    protected static function echo (string $message)
    {
        if (!\Flames\Cli::isCli()) {
            return;
        }

        echo $message;
        try { flush(); } catch (\Throwable $e) {}
        @ob_flush();
    }
}
