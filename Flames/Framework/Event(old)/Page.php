<?php
declare(strict_types=1);


namespace Flames\Event;

use Flames\Framework\Controller\RequestData;

abstract class Page
{
    public function onPreLoad(string $uri) : string|null
    {
        return $uri;
    }

    public function onLoad(string $html) : string|null
    {
        return $html;
    }

    public function onPostLoad() : void
    {

    }
}