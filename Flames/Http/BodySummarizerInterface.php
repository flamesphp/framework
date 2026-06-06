<?php
declare(strict_types=1);


// HttpGuzzle fork: https://github.com/guzzle/guzzle

namespace Flames\Http;

use Flames\Http\Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
