<?php
declare(strict_types=1);


// HttpGuzzle fork: https://github.com/guzzle/guzzle

namespace Flames\Http;

use Flames\Http\Psr\Http\Message\RequestInterface;
use Flames\Http\Psr\Http\Message\ResponseInterface;

interface MessageFormatterInterface
{
    /**
     * Returns a formatted message string.
     *
     * @param RequestInterface       $request  Request that was sent
     * @param ResponseInterface|null $response Response that was received
     * @param \Throwable|null        $error    Exception that was received
     */
    public function format(RequestInterface $request, ResponseInterface $response = null, \Throwable $error = null): string;
}
