<?php
declare(strict_types=1);


// DotEnv fork: https://github.com/vlucas/phpdotenv

namespace Flames\Environment;

/**
 * @internal
 */
class Parser {

    /**
     * Parsed content.
     *
     * @var array
     */
    protected $content = [];

    /**
     * Parsing errors.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Current parsed line number.
     *
     * @var integer
     */
    protected $line = 0;

    /**
     * Reset parser then parse provided string.
     *
     * @param string $string The string to parse
     * @return boolean Success status of parsing
     */
    public function parseString($string) {
        // Reset.
        $this->line = 0;
        $this->content = [];
        $this->errors = [];

        // Split the string into lines.
        $lines = explode("\n", str_replace(["\r\n", "\n\r", "\r"], "\n", $string));

        // Trim each line then parse it.
        foreach ($lines as $line) {
            $this->line++;
            $this->parseLine(trim($line));
        }

        // Expand variables.
        $this->expandVariables();

        // Return success status.
        return empty($this->errors);
    }

    /**
     * Reset parser then parse provided file.
     *
     * @param string $path Path oh the file to parse
     * @return boolean Success status of parsing
     */
    public function parseFile($path) {
        return $this->parseString(file_get_contents($path));
    }

    /**
     * Get parsed content array.
     *
     * @return array
     */
    public function get() {
        return $this->content;
    }

    /**
     * Get parsing errors array.
     *
     * @return array
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Parse a Dotenv file line
     *
     * @param string $line The line to parse
     */
    protected function parseLine($line) {
        // Skip empty lines and comments.
        if (empty($line) || preg_match('/^#/', $line)) {
            return;
        }

        // Get key and value segments.
        $kv = explode("=", $line, 2);
        if (count($kv) !== 2) {
            $this->errors[] = "Line #{$this->line} doesn't respect 'KEY=VALUE' syntax.";
            return;
        }
        $key = trim($kv[0]);
        $value = trim($kv[1]);

        // Check that key format is valid.
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/i', $key)) {
            $this->errors[] = "Line #{$this->line}: key can only contain alphanumeric and underscores, and can't start with a number.";
            return;
        }

        // Parse quoted string value.
        if (preg_match('/^[\'"]/', $value)) {
            $this->content[$key] = $this->parseQuotedValue($value);
            return;
        }

        // Remove comment into value if present, then parse value.
        $comment = strpos($value, '#');
        if ($comment) {
            $value = trim(substr($value, 0, $comment));
        }
        $this->content[$key] = $this->parseUnquotedValue($value);
    }

    /**
     * Parse an unquoted Dotenv line value.
     *
     * @param string $value The line value
     * @return mixed
     */
    protected function parseUnquotedValue($value) {
        // Value is empty or commented or explicitly null.
        if (empty($value) || preg_match('/^#/', $value) || strtolower($value) === 'null') {
            return null;
        }

        // If value contain space, it muyst be quoted.
        if (preg_match('/\s/', $value)) {
            $this->errors[] = "Line #{$this->line}: values containing spaces must be wrapped with quotes.";
            return null;
        }

        // Value is explicitly true.
        if (strtolower($value) === 'true') {
            return true;
        }

        // Value is explicitly false.
        if (strtolower($value) === 'false') {
            return false;
        }

        // Value is numeric.
        if (is_numeric($value)) {
            return (strpos($value, '.') !== false) ? (float) $value : (int) $value;
        }

        // Value is a simple string.
        return $value;
    }

    /**
     * Parse a simple or double quoted Dotenv line value.
     *
     * @param string $value The string to parse
     * @return string The final string value.
     */
    protected function parseQuotedValue($value) {
        // Check if quotes are closed.
        $quote = substr($value, 0, 1);
        if (!preg_match("/{$quote}((?:[^{$quote}\\\\]*(?:\\\\.)?)*){$quote}(.*)/", $value, $matches)) {
            $this->errors[] = "Line #{$this->line}: missing closing quote.";
            return null;
        }

        // Check trailing content.
        if (!empty($matches[2]) && !preg_match('/^#/', trim($matches[2]))) {
            $this->errors[] = "Line #{$this->line}: invalid content after closing quote.";
            return null;
        }

        // Purify the string.
        return strtr($matches[1], ["\\n" => "\n", "\\\"" => "\"", '\\\'' => "'", '\\t' => "\t"]);
    }

    /**
     * Expand variables into parsed content.
     */
    protected function expandVariables() {
        do {
            $expanded = false;

            foreach ($this->content as $key => $value) {
                if ($this->expandLineVariables($key, $value)) {
                    $expanded = true;
                }
            }
        } while ($expanded);
    }

    /**
     * Check if a line value is expandable, or contain expandable(s) variable(s), and expand if needed.
     *
     * @param string $key    The line key
     * @param string $value  The line value
     * @return boolean       Has something been expanded
     */
    protected function expandLineVariables($key, $value) {
        // Value is an expandable variable.
        if ($value === null) {
            return false;
        }

        if (preg_match('/^\$\{([A-Z][A-Z0-9_]*)\}$/', $value, $matches)) {
            $this->content[$key] = isset($this->content[$matches[1]]) ? $this->content[$matches[1]] : null;
            return true;
        }

        // Value contains expandable variables.
        if (preg_match_all('/\$\{([A-Z][A-Z0-9_]*)\}/', $value, $matches)) {
            foreach (array_combine($matches[0], $matches[1]) as $pattern => $var) {
                $replace = isset($this->content[$var]) ? $this->content[$var] : '';
                $value = str_replace($pattern, $replace, $value);
            }

            $this->content[$key] = $value;

            return true;
        }

        // No expandable variable into value.
        return false;
    }

}