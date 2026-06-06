<?php
declare(strict_types=1);


namespace Flames\Event;

use Flames\Framework\Controller\RequestData;

abstract class Output
{
    public function onOutput(RequestData $requestData, string|null $buffer) : string|null
    {
        return $buffer;
    }
}