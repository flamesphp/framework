<?php
declare(strict_types=1);


function mb_str_pad(string $string, int $length, string $pad_string = ' ', int $pad_type = \STR_PAD_RIGHT, ?string $encoding = null): string
{
    if (!\in_array($pad_type, [\STR_PAD_RIGHT, \STR_PAD_LEFT, \STR_PAD_BOTH], true)) {
        throw new \ValueError('mb_str_pad(): Argument #4 ($pad_type) must be STR_PAD_LEFT, STR_PAD_RIGHT, or STR_PAD_BOTH');
    }

    if (null === $encoding) {
        $encoding = mb_internal_encoding();
    }

    try {
        $validEncoding = @mb_check_encoding('', $encoding);
    } catch (\ValueError $e) {
        throw new \ValueError(sprintf('mb_str_pad(): Argument #5 ($encoding) must be a valid encoding, "%s" given', $encoding));
    }

    // BC for PHP 7.3 and lower
    if (!$validEncoding) {
        throw new \ValueError(sprintf('mb_str_pad(): Argument #5 ($encoding) must be a valid encoding, "%s" given', $encoding));
    }

    if (mb_strlen($pad_string, $encoding) <= 0) {
        throw new \ValueError('mb_str_pad(): Argument #3 ($pad_string) must be a non-empty string');
    }

    $paddingRequired = $length - mb_strlen($string, $encoding);

    if ($paddingRequired < 1) {
        return $string;
    }

    switch ($pad_type) {
        case \STR_PAD_LEFT:
            return mb_substr(str_repeat($pad_string, $paddingRequired), 0, $paddingRequired, $encoding).$string;
        case \STR_PAD_RIGHT:
            return $string.mb_substr(str_repeat($pad_string, $paddingRequired), 0, $paddingRequired, $encoding);
        default:
            $leftPaddingLength = floor($paddingRequired / 2);
            $rightPaddingLength = $paddingRequired - $leftPaddingLength;

            return mb_substr(str_repeat($pad_string, $leftPaddingLength), 0, $leftPaddingLength, $encoding).$string.mb_substr(str_repeat($pad_string, $rightPaddingLength), 0, $rightPaddingLength, $encoding);
    }
}