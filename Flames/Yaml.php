<?php

namespace Flames;

class Yaml
{
    public static function parse(?string $yaml = null): mixed
    {
        if ($yaml === null) {
            return null;
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $yaml));
        $pos   = 0;
        $result = null;

        self::parseBlock($lines, $pos, -1, $result);

        return $result;
    }

    public static function stringify(mixed $data, int $indent = 0): string
    {
        if ($data === null) {
            return "~\n";
        }

        if (!is_array($data)) {
            return self::scalarToString($data) . "\n";
        }

        $isList = array_is_list($data);
        $pad    = str_repeat('  ', $indent);
        $out    = '';

        if ($isList) {
            if (empty($data)) {
                return "[]\n";
            }
            foreach ($data as $item) {
                if (is_array($item)) {
                    $out .= $pad . "-\n" . self::stringify($item, $indent + 1);
                } else {
                    $out .= $pad . '- ' . self::scalarToString($item) . "\n";
                }
            }
        } else {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    if (empty($value)) {
                        $out .= $pad . $key . ": []\n";
                    } elseif (array_is_list($value)) {
                        $out .= $pad . $key . ":\n";
                        foreach ($value as $item) {
                            if (is_array($item)) {
                                $out .= $pad . "-\n" . self::stringify($item, $indent + 1);
                            } else {
                                $out .= $pad . '  - ' . self::scalarToString($item) . "\n";
                            }
                        }
                    } else {
                        $out .= $pad . $key . ":\n" . self::stringify($value, $indent + 1);
                    }
                } else {
                    $out .= $pad . $key . ': ' . self::scalarToString($value) . "\n";
                }
            }
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Parser internals
    // -------------------------------------------------------------------------

    protected static function parseBlock(array $lines, int &$pos, int $parentIndent, mixed &$result): void
    {
        $n = count($lines);

        while ($pos < $n) {
            $raw     = $lines[$pos];
            $trimmed = rtrim($raw);
            $lstripped = ltrim($trimmed);

            // Skip blank lines and full-line comments
            if ($lstripped === '' || $lstripped[0] === '#') {
                $pos++;
                continue;
            }

            $indent = strlen($trimmed) - strlen($lstripped);

            // Return control to parent when indentation drops back
            if ($indent <= $parentIndent) {
                return;
            }

            // List item
            if (str_starts_with($lstripped, '- ') || $lstripped === '-') {
                if (!is_array($result)) {
                    $result = [];
                }
                $itemRaw = ltrim(substr($lstripped, 1));
                $pos++;

                if ($itemRaw === '' || $itemRaw === null) {
                    // Block mapping item
                    $child = null;
                    self::parseBlock($lines, $pos, $indent, $child);
                    $result[] = $child ?? [];
                } else {
                    $result[] = self::parseScalar($itemRaw);
                }
                continue;
            }

            // Key: value
            $colonPos = strpos($lstripped, ':');
            if ($colonPos === false) {
                $pos++;
                continue;
            }

            $key  = rtrim(substr($lstripped, 0, $colonPos));
            $rest = ltrim(substr($lstripped, $colonPos + 1));

            // Strip inline comment from rest
            $rest = self::stripInlineComment($rest);

            if (!is_array($result)) {
                $result = [];
            }

            $pos++;

            if ($rest === '[]') {
                $result[$key] = [];
            } elseif ($rest === '' || $rest === null) {
                // Peek at next non-blank line to decide: nested block or null
                $nextIndent = self::peekIndent($lines, $pos, $n);

                if ($nextIndent > $indent) {
                    $child = null;
                    self::parseBlock($lines, $pos, $indent, $child);
                    $result[$key] = $child ?? null;
                } else {
                    $result[$key] = null;
                }
            } else {
                $result[$key] = self::parseScalar($rest);
            }
        }
    }

    protected static function peekIndent(array $lines, int $pos, int $n): int
    {
        while ($pos < $n) {
            $trimmed   = rtrim($lines[$pos]);
            $lstripped = ltrim($trimmed);
            if ($lstripped !== '' && $lstripped[0] !== '#') {
                return strlen($trimmed) - strlen($lstripped);
            }
            $pos++;
        }
        return -1;
    }

    protected static function stripInlineComment(string $value): string
    {
        $inQuote   = false;
        $quoteChar = '';
        $len       = strlen($value);

        for ($i = 0; $i < $len; $i++) {
            $c = $value[$i];
            if (!$inQuote && ($c === '"' || $c === "'")) {
                $inQuote   = true;
                $quoteChar = $c;
            } elseif ($inQuote && $c === $quoteChar) {
                $inQuote = false;
            } elseif (!$inQuote && $c === '#') {
                return rtrim(substr($value, 0, $i));
            }
        }

        return $value;
    }

    protected static function parseScalar(string $value): mixed
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Double-quoted string
        if (strlen($value) >= 2 && $value[0] === '"' && $value[-1] === '"') {
            return stripcslashes(substr($value, 1, -1));
        }

        // Single-quoted string (literal — no escape processing)
        if (strlen($value) >= 2 && $value[0] === "'" && $value[-1] === "'") {
            return substr($value, 1, -1);
        }

        // Boolean
        if ($value === 'true')  return true;
        if ($value === 'false') return false;

        // Null
        if ($value === '~' || $value === 'null') return null;

        // Integer
        if (preg_match('/^-?\d+$/', $value)) {
            return (int)$value;
        }

        // Float
        if (is_numeric($value)) {
            return (float)$value;
        }

        return $value;
    }

    // -------------------------------------------------------------------------
    // Serializer internals
    // -------------------------------------------------------------------------

    protected static function scalarToString(mixed $value): string
    {
        if ($value === null)  return '~';
        if ($value === true)  return 'true';
        if ($value === false) return 'false';
        if (is_int($value) || is_float($value)) return (string)$value;

        $str = (string)$value;

        // Quote if the string contains YAML special characters or whitespace
        if ($str === '' || preg_match('/[\s:#\[\]{},&*!|>\'"%@`]/', $str)) {
            return '"' . addcslashes($str, '"\\') . '"';
        }

        // Quote reserved words that would be misread as scalars
        if (in_array(strtolower($str), ['true', 'false', 'null', '~'], true)) {
            return '"' . $str . '"';
        }

        return $str;
    }
}
