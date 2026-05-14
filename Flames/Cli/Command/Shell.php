<?php

namespace Flames\Cli\Command;

use Flames\Cli\Output;
use Flames\Dump\Dump;

/**
 * @internal
 *
 * Interactive PHP REPL for the Flames framework — analogous to Laravel Tinker.
 * All application classes are available because the Flames autoloader has already run.
 *
 * Features:
 *   - readline with history (~/.flames_shell_history)
 *   - tab-completion for variables and PHP built-in functions
 *   - token-based multi-line buffering (only buffers when { [ ( are unclosed)
 *   - variable persistence across evaluations
 *   - \q, exit, quit to exit
 */
final class Shell
{
    // \x01 / \x02 = RL_PROMPT_START_IGNORE / RL_PROMPT_END_IGNORE
    // These tell readline not to count ANSI bytes when computing cursor position.
    protected const PROMPT      = "\x01\033[38;5;208m\033[1m\x02flames\x01\033[0m\033[90m\x02>>>\x01\033[0m\x02 ";
    protected const PROMPT_CONT = "\x01\033[90m\x02...\x01\033[0m\x02 ";
    protected const HISTORY_FILE = '~/.flames_shell_history';
    private const   SUPER_GLOBALS = ['_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', '_ENV', '_REQUEST', '_SESSION', 'GLOBALS', 'argv', 'argc'];

    public function __construct($data) {}

    public function run(bool $debug = false): bool
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $this->printBanner();

        $historyFile = str_replace('~', $_SERVER['HOME'] ?? '/tmp', self::HISTORY_FILE);

        if (function_exists('readline_read_history') && file_exists($historyFile)) {
            readline_read_history($historyFile);
        }

        $vars   = [];
        $buffer = '';

        if (function_exists('readline_completion_function')) {
            readline_completion_function(function (string $input) use (&$vars): array {
                $line = function_exists('readline_info') ? (string)readline_info('line_buffer') : $input;

                // Object property completion: $var->partial
                if (preg_match('/\$(\w+)\s*->\s*(\w*)$/', $line, $m)) {
                    $varName = $m[1];
                    $partial = $m[2];
                    if (isset($vars[$varName]) && is_object($vars[$varName])) {
                        return array_values(array_filter(
                            array_keys(get_object_vars($vars[$varName])),
                            fn($p) => $partial === '' || str_starts_with($p, $partial)
                        ));
                    }
                    return [];
                }

                // Variable completion: $partial
                if (str_starts_with($input, '$')) {
                    $completions = [];
                    foreach (array_keys($vars) as $name) {
                        $candidate = '$' . $name;
                        if (str_starts_with($candidate, $input)) {
                            $completions[] = $candidate;
                        }
                    }
                    return $completions;
                }

                // PHP built-in function completion
                if ($input !== '') {
                    $lower = strtolower($input);
                    $completions = [];
                    foreach (get_defined_functions()['internal'] as $fn) {
                        if (str_starts_with($fn, $lower)) {
                            $completions[] = $fn;
                        }
                    }
                    return $completions;
                }

                return [];
            });
        }

        // Convert PHP warnings / notices to ErrorException so they are caught by
        // the try-catch inside evaluate() and formatted consistently.
        set_error_handler(static function (int $errno, string $errstr): bool {
            throw new \ErrorException($errstr, 0, $errno);
        });

        try {
            while (true) {
                $prompt = $buffer === '' ? self::PROMPT : self::PROMPT_CONT;
                $line   = $this->readLine($prompt);

                if ($line === false) {
                    echo "\n";
                    break;
                }

                $trimmed = trim($line);

                if (in_array($trimmed, ['\q', 'exit', 'quit', 'exit;', 'quit;'], true)) {
                    break;
                }

                if ($trimmed === '') {
                    if ($buffer !== '') {
                        $this->evaluate($buffer, $vars);
                        $buffer = '';
                    }
                    continue;
                }

                if (function_exists('readline_add_history')) {
                    readline_add_history($line);
                }

                $buffer .= $line . "\n";

                if ($this->isComplete($buffer)) {
                    $this->evaluate($buffer, $vars);
                    $buffer = '';
                }
            }
        } finally {
            restore_error_handler();
        }

        if (function_exists('readline_write_history')) {
            readline_write_history($historyFile);
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Evaluation
    // ─────────────────────────────────────────────────────────────────────────

    protected function evaluate(string $__code, array &$vars): void
    {
        $__code = preg_replace('/^\s*<\?(php)?\s*/i', '', trim($__code));

        if ($__code === '') {
            return;
        }

        if (!preg_match('/[;{}]\s*$/', $__code)) {
            $__code .= ';';
        }

        extract($vars, EXTR_OVERWRITE);

        $__savedCalledFrom = Dump::$displayCalledFrom ?? true;
        Dump::$displayCalledFrom = false;

        // Double-buffered capture:
        //   dump() in Register.php calls @ob_flush() after each echo, which
        //   normally flushes the active buffer straight to stdout.
        //   By opening two levels, the flush goes to the outer level (N-1)
        //   instead of escaping to stdout — so we can capture and clean it.
        //   N-1: receives content flushed via ob_flush()
        //   N:   receives regular echo / print output
        ob_start();  // N-1
        ob_start();  // N

        $__result    = null;
        $__error     = null;
        $__hasResult = false;

        $__expr = rtrim(trim($__code), ';');
        try {
            // Try as expression first so we can display the return value
            $__result    = eval('return (' . $__expr . ');');
            $__hasResult = true;
        } catch (\ParseError $__e) {
            // ParseError fires at compile-time — nothing was output yet;
            // discard the inner buffer and re-try as a full statement block.
            ob_clean();  // clean N (N-1 still empty)
            try {
                eval($__code);
            } catch (\Throwable $__e2) {
                $__error = $__e2;
            }
        } catch (\Throwable $__e) {
            $__error = $__e;
        }

        // Collect output: N holds un-flushed echoes, N-1 holds ob_flush'd content.
        $__fromN   = ob_get_clean();   // get + close N
        $__fromN_1 = ob_get_clean();   // get + close N-1 (accumulated flushes)
        // N-1 was written in the same chronological order as the flushes,
        // and whatever remained in N at the end is appended.
        $__output  = $__fromN_1 . $__fromN;

        if ($__output !== '') {
            echo self::cleanDumpOutput($__output);
        }

        if ($__error !== null) {
            self::printError($__error);
        } elseif ($__hasResult && $__result !== null && $__output === '') {
            // Only show the return value when nothing was already echoed;
            // avoids duplicating output for echo / dump calls.
            self::dumpValue($__result);
        }

        // Restore after all output (including dumpValue) so displayCalledFrom=false
        // covers the entire display phase, not just the eval phase.
        Dump::$displayCalledFrom = $__savedCalledFrom;

        // Persist all user-defined variables.
        // Internal vars use __ prefix to avoid collisions with user vars.
        $__snap = get_defined_vars();
        $vars   = array_filter(
            $__snap,
            fn($key) => !str_starts_with($key, '__') && $key !== 'vars' && !in_array($key, self::SUPER_GLOBALS, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Output helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Display a value using the Flames dump() function, but without the
     * decorative header box and footer separator.
     */
    protected static function dumpValue(mixed $value): void
    {
        // Double ob_start for the same reason as in evaluate():
        // dump()'s @ob_flush() would bypass a single ob layer.
        ob_start();   // N-1: catches ob_flush
        ob_start();   // N:   inner buffer (will be flushed empty by dump)
        \dump($value);
        ob_end_clean();                // discard N (empty after ob_flush)
        $raw = ob_get_clean();         // N-1 has the actual dump content
        echo self::cleanDumpOutput($raw);
    }

    /**
     * Strips the decorative header box (┌ │ └) and separator footer (════)
     * that the Flames dump decorator wraps around each value.
     * Only the actual variable-body lines are kept.
     *
     * Works for output that contains multiple concatenated dump() calls.
     */
    protected static function cleanDumpOutput(string $raw): string
    {
        $lines  = explode("\n", $raw);
        $result = [];

        foreach ($lines as $line) {
            $clean   = preg_replace('/\x1b\[[0-9;]*m/', '', $line);
            $trimmed = ltrim($clean);

            // Header box lines: top border (┌), label row (│), bottom border (└)
            if (str_starts_with($trimmed, '┌') ||
                str_starts_with($trimmed, '│') ||
                str_starts_with($trimmed, '└')) {
                continue;
            }

            // Footer: separator line (════) and call-stack lines
            if (str_starts_with($trimmed, '════')) {
                continue;
            }
            if (str_starts_with($trimmed, 'Call stack')) {
                continue;
            }
            // Numbered trace lines: "        N. File:line"
            if (preg_match('/^\d+\.\s/', $trimmed)) {
                continue;
            }

            $result[] = $line;
        }

        // Trim leading/trailing blank lines
        while ($result && trim(preg_replace('/\x1b\[[0-9;]*m/', '', $result[0])) === '') {
            array_shift($result);
        }
        while ($result && trim(preg_replace('/\x1b\[[0-9;]*m/', '', end($result))) === '') {
            array_pop($result);
        }

        return $result ? implode("\n", $result) . "\n" : '';
    }

    /**
     * Formats a caught Throwable for clean REPL display.
     * ErrorException (from converted PHP warnings) shows the severity label.
     */
    protected static function printError(\Throwable $error): void
    {
        if ($error instanceof \ErrorException) {
            $label = match ($error->getSeverity()) {
                E_WARNING, E_USER_WARNING       => 'Warning',
                E_NOTICE,  E_USER_NOTICE        => 'Notice',
                E_DEPRECATED, E_USER_DEPRECATED => 'Deprecated',
                E_STRICT                        => 'Strict',
                default                         => 'Error',
            };
        } else {
            $label = get_class($error);
        }

        $msg = $error->getMessage();
        // Strip the PHP-appended "in eval()'d code on line N" suffix
        $msg = preg_replace('/\s*in\s+\S*eval\S*.*$/i', '', $msg);

        echo "\033[31m{$label}\033[0m: {$msg}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Completeness detection
    // ─────────────────────────────────────────────────────────────────────────

    protected function isComplete(string $code): bool
    {
        $tokens = @token_get_all('<?php ' . $code);
        $depth  = 0;
        $arr    = 0;
        $par    = 0;

        foreach ($tokens as $token) {
            if (!is_string($token)) {
                continue;
            }
            match ($token) {
                '{'     => $depth++,
                '}'     => $depth--,
                '['     => $arr++,
                ']'     => $arr--,
                '('     => $par++,
                ')'     => $par--,
                default => null,
            };
        }

        return $depth <= 0 && $arr <= 0 && $par <= 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // I/O
    // ─────────────────────────────────────────────────────────────────────────

    protected function readLine(string $prompt): string|false
    {
        if (function_exists('readline')) {
            return readline($prompt);
        }

        echo $prompt;
        $line = fgets(STDIN);
        return $line !== false ? rtrim($line, "\n") : false;
    }

    protected function printBanner(): void
    {
        echo "\n";
        echo Output::ORANGE . Output::BOLD . "  Flames Shell" . Output::RESET;
        echo Output::GRAY   . "  — type \\q or Ctrl+D to exit" . Output::RESET . "\n\n";
    }
}
