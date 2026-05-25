<?php

namespace Flames;

use Flames\Collection\Arr;

class Template
{
    public static function render(string $html, Arr|array $data = null): ?string
    {
        if ($data instanceof Arr) {
            $data = (array)$data;
        } elseif ($data === null) {
            $data = [];
        }

        $loader = new Mesh\Loader\ArrayLoader([
            'index' => $html,
        ]);
        $twig = new Mesh\Environment($loader);
        return $twig->render('index', $data);
    }
}
