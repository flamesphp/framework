<?php
declare(strict_types=1);


function str_decrement(string $string): string
{
    if ('' === $string) {
        throw new \ValueError('str_decrement(): Argument #1 ($string) cannot be empty');
    }

    if (!\preg_match("/^[a-zA-Z0-9]+$/", $string)) {
        throw new \ValueError('str_decrement(): Argument #1 ($string) must be composed only of alphanumeric ASCII characters');
    }

    if (\preg_match('/\A(?:0[aA0]?|[aA])\z/', $string)) {
        throw new \ValueError(sprintf('str_decrement(): Argument #1 ($string) "%s" is out of decrement range', $string));
    }

    if (!\in_array(substr($string, -1), ['A', 'a', '0'], true)) {
        return join('', array_slice(str_split($string), 0, -1)) . chr(ord(substr($string, -1)) - 1);
    }

    $carry = '';
    $decremented = '';

    for ($i = strlen($string) - 1; $i >= 0; $i--) {
        $char = $string[$i];

        switch ($char) {
            case 'A':
                if ('' !== $carry) {
                    $decremented = $carry . $decremented;
                    $carry = '';
                }
                $carry = 'Z';

                break;
            case 'a':
                if ('' !== $carry) {
                    $decremented = $carry . $decremented;
                    $carry = '';
                }
                $carry = 'z';

                break;
            case '0':
                if ('' !== $carry) {
                    $decremented = $carry . $decremented;
                    $carry = '';
                }
                $carry = '9';

                break;
            case '1':
                if ('' !== $carry) {
                    $decremented = $carry . $decremented;
                    $carry = '';
                }

                break;
            default:
                if ('' !== $carry) {
                    $decremented = $carry . $decremented;
                    $carry = '';
                }

                if (!\in_array($char, ['A', 'a', '0'], true)) {
                    $decremented = chr(ord($char) - 1) . $decremented;
                }
        }
    }

    return $decremented;
}