<?php

namespace Flames;

class Html
{
    protected const array SANITIZE_REGEXS = [
        '/\>[^\S ]+/s'   => '>',
        '/[^\S ]+\</s'   => '<',
        '/([\t ])+/s'  => ' ',
        '/^([\t ])+/m' => '',
        '/([\t ])+$/m' => '',
        '~//[a-zA-Z0-9 ]+$~m' => '',
        '/[\r\n]+([\t ]?[\r\n]+)+/s'  => "\n",
        '/\>[\r\n\t ]+\</s'    => '><',
        '/}[\r\n\t ]+/s'  => '}',
        '/}[\r\n\t ]+,[\r\n\t ]+/s'  => '},',
        '/\)[\r\n\t ]?{[\r\n\t ]+/s'  => '){',
        '/,[\r\n\t ]?{[\r\n\t ]+/s'  => ',{',
        '/\),[\r\n\t ]+/s'  => '),',
        '~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s' => '$1$2=$3$4', //$1 and $4 insert first white-space character found before/after attribute
    ];

    protected const array SANITIZE_REMOVES = [
        '</option>',
        '</li>',
        '</dt>',
        '</dd>',
        '</tr>',
        '</th>',
        '</td>'
    ];

    public static function sanitize(string $html): string
    {
        $html = preg_replace(array_keys(self::SANITIZE_REGEXS), array_values(self::SANITIZE_REGEXS), $html);
        $html = str_ireplace(self::SANITIZE_REMOVES, '', $html);

        return $html;
    }
}