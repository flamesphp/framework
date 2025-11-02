<?php

namespace Flames\Template\Source;

/**
 * @internal
 */
final class PostProcess
{
    public static function parse(string $code) : string
    {
        if ($code === '') {
            return $code;
        }

//        $engineBlock =  '{% engine %}';
//        if (str_contains($code, $engineBlock)) {
//            $code = str_replace($engineBlock, '<script src="/.flames.js" type="text/javascript"></script>', $code);
//        }

        return $code;
    }
}
