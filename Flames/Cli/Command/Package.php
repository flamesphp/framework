<?php

namespace Flames\Cli\Command;

use Flames\Cli\Output;

/**
 * @internal
 *
 * Wraps the bundled composer.phar so that packages can be managed without
 * requiring a system-wide Composer installation.
 *
 * Usage examples:
 *   forge library require vendor/package
 *   forge library remove vendor/package
 *   forge library update
 *   forge library show
 *   forge library audit
 *   forge library validate
 *   forge library {any-composer-command} [args...]
 */
final class Package
{
    // Raw args after "package" (unprocessed so they go straight to composer)
    protected array $args = [];

    public function __construct($data)
    {
        $this->args = array_values(array_slice($_SERVER['argv'], 2));
    }

    public function run(bool $debug = false): bool
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $composerPhar = self::composerPath();

        if ($composerPhar === null) {
            Output::error('composer.phar not found in Flames/Kernel/Tools/.');
            return false;
        }

        $phpBin = escapeshellcmd(PHP_BINARY);

        if (empty($this->args) === true) {
            // No sub-command → show composer help
            passthru($phpBin . ' ' . escapeshellarg($composerPhar) . ' --ansi list', $code);
            return $code === 0;
        }

        // Build the composer command — pass all args verbatim
        $argsStr = implode(' ', array_map('escapeshellarg', $this->args));

        // Always add --ansi so output is colourful in the terminal
        $cmd = $phpBin . ' ' . escapeshellarg($composerPhar) . ' --ansi ' . $argsStr;

        chdir(ROOT_PATH);
        passthru($cmd, $code);
        return $code === 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns the absolute path to the bundled composer.phar, or null if not found.
     */
    public static function composerPath(): ?string
    {
        $path = FLAMES_PATH . 'Kernel/Tools/composer.phar';
        return file_exists($path) ? $path : null;
    }
}
