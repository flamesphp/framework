<?php
declare(strict_types=1);


// DotEnv fork: https://github.com/vlucas/phpdotenv

namespace Flames\Environment;

/**
 * @internal
 */
class Helpers {

    /**
     * Parse provided string, throw an exception if invalid, otherwise return parsed content as a key-value array.
     *
     * @param string $string The string to parse
     * @param array $defaults An array of defaults values
     * @return array The parsed content
     *
     * @throws \UnexpectedValueException
     */
    public static function fromString($string, array $defaults = []) {
        // Instanciate Dotenv parser.
        $parser = new \Flames\Environment\Parser();

        // Parse the string and throw an exception on failure.
        if (!$parser->parseString($string)) {
            $count = count($parser->errors());
            $errors = implode(' ', $parser->errors());
            throw new \UnexpectedValueException("{$count} error(s) where detected while parsing dotenv. {$errors}");
        }

        // Set defaults values if needed.
        $content = $parser->get();
        foreach ($defaults as $k => $v) {
            if (!isset($content[$k]) || (empty($content[$k]) && $v !== false)) {
                $content[$k] = $v;
            }
        }

        // Return content.
        return $content;
    }

    /**
     * Parse provided file, throw an exception if invalid, otherwise return parsed content as a key-value array.
     *
     * @param string $path The file to parse
     * @param array $defaults An array of defaults values
     * @return array The parsed content
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public static function fromFile($path, array $defaults = []) {
        // Check that provided file exists.
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Dotenv file doesn't exists: $path");
        }

        // Return content.
        return self::fromString(file_get_contents($path), $defaults);
    }

}