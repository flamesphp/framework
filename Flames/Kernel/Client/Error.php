<?php

namespace Flames\Kernel\Client;

use Flames\Js;

/**
 * @internal
 */
final class Error
{
    public static function handler(\Exception|\Error $e): void
    {
        $error = Js::getWindow()->console->error;

        $message = ($e->getMessage() . "\r\n\r\n");

        $maxClassLength = 0;
        $maxLineLength = 0;
        $_traces = $e->getTrace();
        $traces = [];

        for ($i = 0; $i < count($_traces); $i++) {
            if ($i === 0) {
                $traces[] = [
                    'class' => $_traces[$i]['class'],
                    'function' => $_traces[$i]['function'],
                    'line' => $e->getLine()
                ];

                continue;
            }
            $traces[] = [
                'class' => $_traces[$i]['class'],
                'function' => $_traces[$i]['function'],
                'line' => $_traces[$i - 1]['line']
            ];
        }

        foreach ($traces as $trace) {
            $classLength = strlen($trace['class'] . ':' . $trace['function'] . '()');
            if ($classLength > $maxClassLength) {
                $maxClassLength = $classLength;
            }

            $lineLength = strlen(($trace['line'] !== null) ? $trace['line'] : '0');
            if ($lineLength > $maxLineLength) {
                $maxLineLength = $lineLength;
            }
        }

        foreach ($traces as $trace) {
            $classTrace = ($trace['class'] . ':' . $trace['function'] . '()');
            while (strlen($classTrace) < $maxClassLength) {
                $classTrace .= ' ';
            }

            $lineTrace = (($trace['line'] !== null) ? $trace['line'] : '0');
            while (strlen($lineTrace) < $maxLineLength) {
                $lineTrace = (' ' . $lineTrace);
            }

            $message .= ($classTrace . ' | Line ' . $lineTrace . "\r\n");
        }

        $error($message);
    }
}