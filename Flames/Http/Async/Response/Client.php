<?php
declare(strict_types=1);


namespace Flames\Http\Async\Response;

use Flames\Collection\Arr;

/**
 * @internal
 */
class Client
{
    private int $code;
    private string|null $body;
    private Arr $headers;

    public function __construct(int $code = 200, string $body = null, array|Arr $headers = [])
    {
        $this->code = $code;
        $this->body = $body;
        $this->headers = Arr($headers);
    }

    public function getStatusCode() : int
    {
        return $this->code;
    }

    public function getBody() : string|null
    {
        return $this->body;
    }

    public function getJson() : Arr
    {
        return Arr((array)json_decode($this->getBody()));
    }
}