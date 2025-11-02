<?php

namespace Flames\Client;

use Flames\Js;

class Clipboard
{
    public static function copy(string $data)
    {
        $browser = Browser::getName();

        try {
            $browser = mb_strtolower($browser, 'UTF-8');
        } catch (\Exception|\Error $e) { $browser = strtolower($browser); }

//        if ($browser === 'firefox') {
//            self::copySecure($data);
//        }

        self::copyInsecure($data);
    }

    protected static function copyInsecure(string $data)
    {
        Js::eval("
            var body = window.document.body;
            if (body !== null) {
 
                body.insertAdjacentHTML('beforeend', '<input id=\"flames-clipboard\" type=\"text\" value=\"\" style=\"position: absolute; top: calc(100% + 200px); opacity: 0.000000001;\"/>');
            }
        ");

        $window = Js::getWindow();
        $element = $window->document->querySelector('#flames-clipboard');
        if ($element === null) {
            return null;
        }
        $element->value = $data;
        $element->select();
        Js::eval('window.document.execCommand("copy")');
        $element->remove();
    }
}