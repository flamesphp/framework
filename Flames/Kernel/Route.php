<?php
declare(strict_types=1);


namespace Flames\Kernel;

use Flames\Collection\Arr;
use Flames\Framework\Controller\RequestMount;
use Flames\Framework\Controller\RequestData;

/**
 * @internal
 * @deprecated Use Flames\Framework\Controller\RequestMount instead.
 */
class Route
{
    public static function mountRequestData(Arr $routeData, string|null $ip = null): RequestData
    {
        return RequestMount::mountRequestData($routeData, $ip);
    }
}
