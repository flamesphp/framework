<?php

// Twig fork: https://github.com/twigphp/Twig

namespace Flames\Template;

/**
 * @internal
 */
class FileExtensionEscapingStrategy
{
    /**
     * Guesses the best autoescaping strategy based on the file name.
     *
     * @param string $name The template name
     *
     * @return string|false The escaping strategy name to use or false to disable
     */
    public static function guess(string $name)
    {
        if (\in_array(substr($name, -1), ['/', '\\'])) {
            return 'html'; // return html for directories
        }

        if (str_ends_with($name, '.twig')) {
            $name = substr($name, 0, -5);
        }

        $extension = pathinfo($name, \PATHINFO_EXTENSION);

        switch ($extension) {
            case 'js':
                return 'js';

            case 'css':
                return 'css';

            case 'txt':
                return false;

            default:
                return 'html';
        }
    }
}
