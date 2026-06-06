<?php
declare(strict_types=1);


namespace Flames\Http\Psr\Http\Client;

use Flames\Http\Psr\Http\Message\RequestInterface;
use Flames\Http\Psr\Http\Message\ResponseInterface;

interface ClientInterface
{
    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \Flames\Http\Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
