<?php

namespace Flames\Cli\Command;

use Flames\Cli\Output;
use Flames\Server\Os;

/**
 * @internal
 *
 * Sets up the global "forge" launcher so the user can run "forge" from any
 * directory without typing "./forge".
 *
 * On Unix: installs a shell wrapper in ~/.local/bin/forge and ensures
 *          ~/.local/bin is in PATH via ~/.bashrc.
 * On Windows: displays manual instructions (PATH setup is environment-specific).
 */
final class Inject
{
    public function __construct($data) {}

    public function run(bool $debug = false): bool
    {
        if (Os::isUnix() === false) {
            Output::warning('Automatic setup is only supported on Unix/Linux/macOS.');
            Output::info('Add the project directory to your PATH manually, or run forge via: php forge {command}');
            return false;
        }

        $home      = $_SERVER['HOME'] ?? posix_getpwuid(posix_getuid())['dir'] ?? null;
        if ($home === null) {
            Output::error('Could not determine home directory.');
            return false;
        }

        $localBin    = $home . '/.local/bin';
        $wrapperPath = $localBin . '/forge';

        // ── 1. Create ~/.local/bin if needed ──────────────────────────────────
        if (is_dir($localBin) === false) {
            mkdir($localBin, 0755, true);
            Output::success("Created {$localBin}");
        }

        // ── 2. Write the global wrapper (skip if already identical) ──────────
        $wrapperContent = <<<'BASH'
#!/bin/bash
# Global forge launcher — walks up the directory tree to find a project's forge file.
dir="$PWD"
while [ "$dir" != "/" ]; do
    if [ -f "$dir/forge" ] && [ -x "$dir/forge" ]; then
        exec "$dir/forge" "$@"
    fi
    dir="$(dirname "$dir")"
done
echo "forge: No forge file found in the current or any parent directory." >&2
exit 1
BASH;

        $alreadyInstalled = false;
        if (file_exists($wrapperPath) && file_get_contents($wrapperPath) === $wrapperContent) {
            $alreadyInstalled = true;
            Output::info("Global wrapper already installed → {$wrapperPath}");
        } else {
            file_put_contents($wrapperPath, $wrapperContent);
            chmod($wrapperPath, 0755);
            Output::success("Installed global wrapper → {$wrapperPath}");
        }

        // ── 3. Add ~/.local/bin to PATH in ~/.bashrc ──────────────────────────
        $bashrc       = $home . '/.bashrc';
        $pathSnippet  = 'if [ -d "$HOME/.local/bin" ]; then export PATH="$HOME/.local/bin:$PATH"; fi';

        $bashrcAlready = file_exists($bashrc) && str_contains(file_get_contents($bashrc), '.local/bin');
        if ($bashrcAlready) {
            Output::info('~/.local/bin already present in ~/.bashrc — skipping.');
        } else {
            file_put_contents($bashrc, "\n# forge global launcher\n{$pathSnippet}\n", FILE_APPEND);
            Output::success('Added ~/.local/bin to PATH in ~/.bashrc');
        }

        if ($alreadyInstalled && $bashrcAlready) {
            Output::blank();
            Output::info('forge is already fully injected. Nothing was changed.');
            return true;
        }

        Output::blank();
        Output::info('Run the following to activate forge in the current session:');
        echo '    ' . Output::CYAN . 'source ~/.bashrc' . Output::RESET . "\n";

        return true;
    }
}
