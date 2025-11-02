<?php

namespace Flames\Template\Client
{
    /**
     * @internal
     */
    class Polyfill
    {
        public static function load() {}

        public static function ctype_alnum($text)
        {
            $text = self::convert_int_to_char_for_ctype($text, __FUNCTION__);

            return \is_string($text) && '' !== $text && !preg_match('/[^A-Za-z0-9]/', $text);
        }

        public static function ctype_alpha($text)
        {
            $text = self::convert_int_to_char_for_ctype($text, __FUNCTION__);

            return \is_string($text) && '' !== $text && !preg_match('/[^A-Za-z]/', $text);
        }

        public static function ctype_cntrl($text)
        {
            $text = self::convert_int_to_char_for_ctype($text, __FUNCTION__);

            return \is_string($text) && '' !== $text && !preg_match('/[^\x00-\x1f\x7f]/', $text);
        }

        public static function ctype_digit($text)
        {
            $text = self::convert_int_to_char_for_ctype($text, __FUNCTION__);

            return \is_string($text) && '' !== $text && !preg_match('/[^0-9]/', $text);
        }

        public static function ctype_graph($text)
        {
            $text = self::convert_int_to_char_for_ctype($text, __FUNCTION__);

            return \is_string($text) && '' !== $text && !preg_match('/[^!-~]/', $text);
        }

        public static function ctype_lower($text)
        {
            $text = self::convert_int_to_char_for_ctype($text, __FUNCTION__);

            return \is_string($text) && '' !== $text && !preg_match('/[^a-z]/', $text);
        }

        public static function ctype_print($text)
        {
            $text = self::convert_int_to_char_for_ctype($text, __FUNCTION__);

            return \is_string($text) && '' !== $text && !preg_match('/[^ -~]/', $text);
        }

        public static function ctype_punct($text)
        {
            $text = self::convert_int_to_char_for_ctype($text, __FUNCTION__);

            return \is_string($text) && '' !== $text && !preg_match('/[^!-\/\:-@\[-`\{-~]/', $text);
        }

        public static function ctype_space($text)
        {
            $text = self::convert_int_to_char_for_ctype($text, __FUNCTION__);

            return \is_string($text) && '' !== $text && !preg_match('/[^\s]/', $text);
        }

        public static function ctype_upper($text)
        {
            $text = self::convert_int_to_char_for_ctype($text, __FUNCTION__);

            return \is_string($text) && '' !== $text && !preg_match('/[^A-Z]/', $text);
        }

        public static function ctype_xdigit($text)
        {
            $text = self::convert_int_to_char_for_ctype($text, __FUNCTION__);

            return \is_string($text) && '' !== $text && !preg_match('/[^A-Fa-f0-9]/', $text);
        }

        private static function convert_int_to_char_for_ctype($int, $function)
        {
            if (!\is_int($int)) {
                return $int;
            }

            if ($int < -128 || $int > 255) {
                return (string) $int;
            }

            if (\PHP_VERSION_ID >= 80100) {
                @trigger_error($function.'(): Argument of type int will be interpreted as string in the future', \E_USER_DEPRECATED);
            }

            if ($int < 0) {
                $int += 256;
            }

            return \chr($int);
        }
    }
}

namespace {
    if (!function_exists('ctype_alnum')) {
        function ctype_alnum($text) { return \Flames\Template\Client\Polyfill::ctype_alnum($text); }
    }
    if (!function_exists('ctype_alpha')) {
        function ctype_alpha($text) { return \Flames\Template\Client\Polyfill::ctype_alpha($text); }
    }
    if (!function_exists('ctype_cntrl')) {
        function ctype_cntrl($text) { return \Flames\Template\Client\Polyfill::ctype_cntrl($text); }
    }
    if (!function_exists('ctype_digit')) {
        function ctype_digit($text) { return \Flames\Template\Client\Polyfill::ctype_digit($text); }
    }
    if (!function_exists('ctype_graph')) {
        function ctype_graph($text) { return \Flames\Template\Client\Polyfill::ctype_graph($text); }
    }
    if (!function_exists('ctype_lower')) {
        function ctype_lower($text) { return \Flames\Template\Client\Polyfill::ctype_lower($text); }
    }
    if (!function_exists('ctype_print')) {
        function ctype_print($text) { return \Flames\Template\Client\Polyfill::ctype_print($text); }
    }
    if (!function_exists('ctype_punct')) {
        function ctype_punct($text) { return \Flames\Template\Client\Polyfill::ctype_punct($text); }
    }
    if (!function_exists('ctype_space')) {
        function ctype_space($text) { return \Flames\Template\Client\Polyfill::ctype_space($text); }
    }
    if (!function_exists('ctype_upper')) {
        function ctype_upper($text) { return \Flames\Template\Client\Polyfill::ctype_upper($text); }
    }
    if (!function_exists('ctype_xdigit')) {
        function ctype_xdigit($text) { return \Flames\Template\Client\Polyfill::ctype_xdigit($text); }
    }
}


