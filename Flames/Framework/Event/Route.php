<?php
declare(strict_types=1);


namespace Flames\Framework\Event;

use Flames\Interfaces\Event\Route as RouteContract;
use Flames\Framework\Controller\RequestData;

abstract class Route implements RouteContract
{
    public function onRoute(): void
    {
    }

    public function onMatch(RequestData $requestData): bool
    {
        return true;
    }
}